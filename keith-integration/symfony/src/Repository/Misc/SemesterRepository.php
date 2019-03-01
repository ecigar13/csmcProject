<?php


namespace App\Repository\Misc;


use Doctrine\ORM\EntityRepository;

class SemesterRepository extends EntityRepository {
    public function findActive() {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.active = true');
        return $qb->getQuery()->getOneOrNullResult();
    }
}