<?php

namespace App\Repository\Misc;

use App\Entity\User\User;
use Doctrine\ORM\EntityRepository;

class AnnouncementRepository extends EntityRepository {
    public function findActiveFor(User $user = null) {
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $qb = $this->createQueryBuilder('a');

        if ($user == null) {
            return array();
        } else {
            $qb->join('a.roles', 'r')
                ->join('r.users', 'u')
                ->where('u = :user')
                ->andWhere('a.active = 1')
                ->andWhere('a.startDate <= :today')
                ->andWhere('a.endDate >= :today')
                ->setParameter('user', $user)
                ->setParameter('today', $today);
        }
        return $qb->getQuery()->getResult();

    }
}