<?php


namespace App\Tests\TestUtils;


/**
 * Contains utility methods to perform reflection on test data. This allows us to initialize and modify model objects
 * in ways that are not permitted by the domain for the purpose of more convenient testing.
 *
 * @package App\Tests\Utils
 */
class ReflectionUtils
{
    /**
     * Sets the value of a property that wouldn't normally be accessible in this context.
     *
     * @param $object
     * @param string $propertyName
     * @param null $value
     */
    public static function assignValueToPrivateProperty($object, string $propertyName, $value = null)
    {
        $reflectionObject = new \ReflectionObject($object);
        if ($reflectionObject->hasProperty($propertyName)) {
            $property = $reflectionObject->getProperty($propertyName);
        } else {
            $parentClass = $reflectionObject->getParentClass();

            // Search through the whole hierarchy until the property is found or there is nowhere else to look
            while ($parentClass != null && !$parentClass->hasProperty($propertyName)) {
                $parentClass = $parentClass->getParentClass();
            }

            if ($parentClass != null && $parentClass->hasProperty($propertyName)) {
                $property = $parentClass->getProperty($propertyName);
            } else {
                throw new \InvalidArgumentException(
                    sprintf("Class %s or ancestors have no property %s", get_class($object), $propertyName));
            }
        }

        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * Gets the value of a property that wouldn't normally be accessible in this context.
     *
     * @param $object
     * @param string $propertyName
     * @return mixed
     */
    public static function extractPrivatePropertyValue($object, string $propertyName)
    {
        $reflectionObject = new \ReflectionObject($object);
        $property = $reflectionObject->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * @param string $className
     * @return object
     * @throws \ReflectionException
     */
    public static function createWithoutConstructor(string $className)
    {
        $reflectionClass = new \ReflectionClass($className);

        return $reflectionClass->newInstanceWithoutConstructor();
    }
}