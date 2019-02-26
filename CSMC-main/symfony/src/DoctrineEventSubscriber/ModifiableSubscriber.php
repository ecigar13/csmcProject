<?php

namespace App\DoctrineEventSubscriber;

use App\Entity\Interfaces\ModifiableInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ModifiableSubscriber implements EventSubscriber {
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage) {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents() {
        return array(
            'prePersist',
            'preUpdate'
        );
    }

    public function prePersist(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        if(!$entity instanceof ModifiableInterface) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        $entity->setCreatedOn();
        // $entity->setCreatedBy($user);

        $entity->setLastModifiedOn();
        // $entity->setLastModifiedBy($user);
    }

    public function preUpdate(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        if(!$entity instanceof ModifiableInterface) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        $entity->setLastModifiedOn();
        // $entity->setLastModifiedBy($user);
    }
}