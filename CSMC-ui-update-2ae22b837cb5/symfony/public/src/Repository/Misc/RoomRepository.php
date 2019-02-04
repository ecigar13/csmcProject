<?php

namespace App\Repository\Misc;

use Doctrine\ORM\EntityRepository;

class RoomRepository extends EntityRepository {
    public function findActive() {
        $qb = $this->createQueryBuilder('r');
        $qb->where('r.active = :active')
            ->setParameter('active', true);

        return $qb->getQuery()->getResult();
    }
}