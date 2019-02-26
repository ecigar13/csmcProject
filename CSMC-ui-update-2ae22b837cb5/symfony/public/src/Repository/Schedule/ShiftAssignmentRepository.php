<?php

namespace App\Repository\Schedule;

use App\Entity\User\User;
use Doctrine\ORM\EntityRepository;

class ShiftAssignmentRepository extends EntityRepository {
    public function findForTimes(\DateTime $date, \DateTime $time) {
        $qb = $this->createQueryBuilder('a');

        $qb->join('a.scheduledShift', 'ss')
            ->join('ss.shift', 's')
            ->where('ss.date = :date')
            ->andWhere('s.startTime = :start')
            ->setParameters(array(
                'date' => new \DateTime($date->format('m/d/Y')),
                'start' => new \DateTime($time->format('H:i'))
            ));

        return $qb->getQuery()->getResult();
    }

    public function findByUserAndDate(User $user, \DateTime $date) {
        $qb = $this->createQueryBuilder('a');

        $qb->join('a.scheduledShift', 'ss')
            ->where('ss.date = :date')
            ->andWhere('a.mentor = :user')
            ->join('ss.shift', 's')
            ->orderBy('s.startTime')
            ->setParameters(array(
                'date' => $date,
                'user' => $user
            ));

        return $qb->getQuery()->getResult();
    }
}