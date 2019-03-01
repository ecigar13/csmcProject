<?php

namespace App\DoctrineEventSubscriber;

use App\Annotation\Uploadable;
use App\Utils\FileUploader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Util\ClassUtils;
use Psr\Log\LoggerInterface;

class FileSubscriber implements EventSubscriber {
    private $reader;
    private $logger;
    private $uploader;

    public function __construct(Reader $reader, FileUploader $fileUploader, LoggerInterface $logger) {
        $this->reader = $reader;
        $this->uploader = $fileUploader;
        $this->logger = $logger;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents() {
        return [
            'prePersist',
            'preUpdate'
        ];
    }

    public function prePersist(LifecycleEventArgs $args) {
        $entity = $args->getObject();

        if (!$this->isUploadable($entity) /*|| !$this->hasUploadable($entity) */) {
            return;
        }

        $this->uploader->upload($entity);
    }

    public function preUpdate(LifecycleEventArgs $args) {
    }

    private function isUploadable($entity) {
        $annotation = $this->reader->getClassAnnotation(new \ReflectionClass(ClassUtils::getClass($entity)), Uploadable::class);
        return $annotation !== null;
    }

    private function hasUploadable($entity) {
        $class = new \ReflectionClass(ClassUtils::getClass($entity));
        foreach($class->getProperties() as $p) {
            $annotation = $this->reader->getPropertyAnnotation($p, Uploadable::class);
            if($annotation !== null) {
                return true;
            }
        }

        return false;
    }
}
