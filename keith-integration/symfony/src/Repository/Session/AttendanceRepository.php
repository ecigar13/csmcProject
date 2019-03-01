<?php

namespace App\Repository\Session;

use App\Entity\User\User;
use Doctrine\ORM\EntityRepository;

class AttendanceRepository extends EntityRepository {
    public function findCurrent(User $user = null) {
        $qb = $this->createQueryBuilder('a');
        $qb->where($qb->expr()->gte('a.timeIn', ':today'))
            ->andWhere($qb->expr()->isNull('a.timeOut'))
            ->setParameter('today', (new \DateTime())->setTime(0, 0, 0));
        if ($user) {
            $qb->andWhere('a.user = :user')
                ->setParameter('user', $user);

            return $qb->getQuery()->getOneOrNullResult();
        }

        return $qb->getQuery()->getResult();
    }
}