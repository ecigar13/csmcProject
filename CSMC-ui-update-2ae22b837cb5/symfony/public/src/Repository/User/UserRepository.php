<?php

namespace App\Repository\User;

use App\Utils\SwipeManager;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository {
    public function findByRole(string $name) {
        $qb = $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.name = :role')
            ->setParameters(array(
                'role' => $name
            ));
        return $qb->getQuery()->getResult();
    }

    public function findByCardId($cardId) {
        $qb = $this->createQueryBuilder('u');

        if (SwipeManager::isScancodeLegacy($cardId)) {
            $qb->where('u.scancode = :card');
        } else {
            $qb->where('u.cardId = :card');
        }

        $qb->setParameters(array(
            'card' => $cardId
        ));

        return $qb->getQuery()->getOneOrNullResult();
    }
}