<?php

namespace App\Repository\Schedule;

use App\Entity\User\User;
use Doctrine\ORM\EntityRepository;

class TimesheetRepository extends EntityRepository {
    public function findOnDuty(User $user = null) {
        $qb = $this->createQueryBuilder('t');
        $qb->where($qb->expr()->gte('t.timeIn', ':today'))
            ->andWhere($qb->expr()->isNull('t.timeOut'))
            ->setParameter('today', (new \DateTime())->setTime(0, 0, 0));

        if ($user) {
            $qb->andWhere('t.user = :user')
                ->setParameter('user', $user);

            return $qb->getQuery()->getOneOrNullResult();
        }

        return $qb->getQuery()->getResult();
    }

    public function findByUserAndDay(User $user, \DateTime $day) {
        $qb = $this->createQueryBuilder('t');
        $qb->where($qb->expr()->andX('t.timeIn > :day_start', 't.timeIn < :day_end'))
            ->andWhere('t.user = :user')
            ->setParameters(array(
                'day_start' => $day->format('Y-m-d 00:00:00'),
                'day_end' => $day->format('Y-m-d 23:59:59'),
                'user' => $user
            ));

        return $qb->getQuery()->getResult();
    }

    public function findByUserAndDates(User $user, \DateTime $start, \DateTime $end) {
        $qb = $this->createQueryBuilder('t');
        $qb->where($qb->expr()->andX('t.timeIn > :start', 't.timeIn < :end'))
            ->andWhere('t.user = :user')
            ->orderBy('t.timeIn', 'ASC')
            ->setParameters(array(
                'start' => $start->format('Y-m-d 00:00:00'),
                'end' => $end->format('Y-m-d 23:59:59'),
                'user' => $user
            ));

        return $qb->getQuery()->getResult();
    }

}