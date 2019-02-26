<?php

namespace App\Repository\Session;

use App\DBAL\Types\RequestStatusType;
use Doctrine\ORM\EntityRepository;

class RequestRepository extends EntityRepository {
    public function findNew() {
        $qb = $this->createQueryBuilder('r');

        $qb->where('r.status = :status')
            ->setParameter('status', RequestStatusType::NEW);

        return $qb->getQuery()
            ->getResult();
    }
}