<?php

namespace App\DoctrineEventSubscriber;

use App\Annotation\Timestampable;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

class TimestampableSubscriber implements EventSubscriber {
    private $reader;
    private $logger;

    public function __construct(Reader $reader, LoggerInterface $logger) {
        $this->reader = $reader;
        $this->logger = $logger;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents() {
        return array(
            Events::prePersist,
            Events::preUpdate
        );
    }

    public function prePersist(LifecycleEventArgs $args) {
        $entity = $args->getEntity();

        $this->logger->info('Entity of type "' . get_class($entity) . '" is timestampable: ' . $this->isTimestampable($entity));

        if (!$this->isTimestampable($entity)) {
            return;
        }

        $class = new \ReflectionClass(ClassUtils::getClass($entity));

        $time = new \DateTime();

        $created = $class->getProperty(Timestampable::CREATED);
        $created->setAccessible(true);
        $created->setValue($entity,$time);

        $updated = $class->getProperty(Timestampable::UPDATED);
        $updated->setAccessible(true);
        $updated->setValue($entity,$time);
    }

    public function preUpdate(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        if (!$this->isTimestampable($entity)) {
            return;
        }

        $class = new \ReflectionClass(ClassUtils::getClass($entity));

        $updated = $class->getProperty(Timestampable::UPDATED);
        $updated->setAccessible(true);
        $updated->setValue($entity,new \DateTime());
    }

    private function isTimestampable($entity) {
        $annotation = $this->reader->getClassAnnotation(new \ReflectionClass(ClassUtils::getClass($entity)), Timestampable::class);
        return $annotation !== null;
    }
}