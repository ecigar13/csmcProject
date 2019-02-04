<?php

namespace App\Utils;

use Doctrine\ORM\Proxy\Proxy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

class Serializer {
    const CIRCULAR_REFERENCE = 'circular_reference';
    const ATTRIBUTES = 'attributes';
    const CALLBACKS = 'callbacks';

    private $logger;
    private $dateTimeFormat = 'c'; // ISO-8601 format

    // private $callbacks = array();
    private $ignoredAttributes = array();
    private $maxDepth; // TODO need to handle a max depth, somethings will cause memory to be exhausted

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function serialize($data, array $context = array()) {
        $normalized = $this->normalize($data, $context);

        $encoded = json_encode($normalized);

        return $encoded;
    }

    public function normalize($data, array $context = array()) {
        if ($data === null || is_scalar($data)) {
            return $data;
        }

        if (is_array($data) || $data instanceof \Traversable) {
            $normalized = array();
            foreach ($data as $key => $value) {
                $normalized[$key] = $this->normalize($value, $context);
            }

            return $normalized;
        }

        if (is_object($data)) {
            if ($this->isCircularReference($data, $context)) {
                return $data->getId(); // TODO maybe change
                // return null;
            }

            $normalized = array();
            $attributes = $this->getAttributes($data, $context);

            foreach ($attributes as $attribute) {
                $value = $this->getAttributeValue($data, $attribute, $context);

                if ($value instanceof Proxy) {
                    $value->__load();
                }

                if(isset($context[self::CALLBACKS][$attribute])) {
                    $value = call_user_func($context[self::CALLBACKS][$attribute], $value);
                }

                if ($value instanceof \DateTime) {
                    $value = $value->format($this->dateTimeFormat);
                }

                if ($value !== null && !is_scalar($value)) {
                    $value = $this->normalize($value, $this->createChildContext($context, $attribute));
                }

                $normalized[$attribute] = $value;
            }

            return $normalized;
        }

        throw new Exception(sprintf('Cannot normalize value of type %s', var_export($data, true)));
    }

    private function isAllowedAttribute($attribute, array $context = array()) {
        if (in_array($attribute, $this->ignoredAttributes)) {
            return false;
        }

        if (isset($context[self::ATTRIBUTES][$attribute])) {
            return true;
        }

        if (isset($context[self::ATTRIBUTES]) && is_array($context[self::ATTRIBUTES])) {
            return in_array($attribute, $context[self::ATTRIBUTES], true);
        }

        return true;
    }

    private function getAttributes($object, array $context) {
        $reflection_object = new \ReflectionObject($object);
        $attributes = array();

        do {
            foreach ($reflection_object->getProperties() as $property) {
                if (!$this->isAllowedAttribute($property->name, $context)) {
                    continue;
                }

                $attributes[] = $property->name;
            }

        } while ($reflection_object = $reflection_object->getParentClass());

        return $attributes;
    }

    private function getAttributeValue($object, $attribute, array $context = array()) {
        $reflection_property = $this->getReflectionProperty($object, $attribute);
        if($reflection_property === null) {
            return null;
        }

        if (!$reflection_property->isPublic()) {
            $reflection_property->setAccessible(true);
        }

        $value = $reflection_property->getValue($object);

        return $value;
    }

    private function getReflectionProperty($object, $attribute) {
        $reflection_class = new \ReflectionClass($object);
        while (true) {
            try {
                return $reflection_class->getProperty($attribute);
            } catch (\ReflectionException $exception) {
                if (!$reflection_class = $reflection_class->getParentClass()) {
                    return null;
                }
            }
        }
    }

    private function isCircularReference($object, &$context) {
        $hash = spl_object_hash($object);
        if (isset($context[self::CIRCULAR_REFERENCE][$hash])) {
            unset($context[self::CIRCULAR_REFERENCE][$hash]);

            return true;
        }

        $context[self::CIRCULAR_REFERENCE][$hash] = true;

        return false;
    }

    private function createChildContext(array $context, $attribute) {
        if (isset($context[self::ATTRIBUTES][$attribute])) {
            $context[self::ATTRIBUTES] = $context[self::ATTRIBUTES][$attribute];
        } else {
            unset($context[self::ATTRIBUTES]);
        }

        return $context;
    }
}