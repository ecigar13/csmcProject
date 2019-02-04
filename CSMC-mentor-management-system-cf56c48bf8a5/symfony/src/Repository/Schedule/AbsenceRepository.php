<?php

namespace App\Repository\Schedule;

use App\Entity\User\User;
use Doctrine\ORM\EntityRepository;

class AbsenceRepository extends EntityRepository {
    public function findAllUpcoming() {
        $qb = $this->createQueryBuilder('a');
        $qb->join('a.assignment', 'm')
            ->join('m.scheduledShift', 's')
            // ->where($qb->expr()->gte('s.date', ':today'))
            ->join('s.shift', 'h')
            ->orderBy('h.startTime', 'DESC')
            ->orderBy('s.date', 'DESC');
            // ->setParameter('today', new \DateTime());

        return $qb->getQuery()->getResult();
    }

    public function findAllUpcomingFor(User $user) {
        $qb = $this->createQueryBuilder('a');
        $qb->join('a.assignment', 'm')
            ->join('m.scheduledShift', 's')
            ->where($qb->expr()->gte('s.date', ':today'))
            ->andWhere($qb->expr()->eq('m.mentor', ':user'))
            ->join('s.shift', 'h')
            ->orderBy('h.startTime')
            ->orderBy('s.date')
            ->setParameter('today', new \DateTime())
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    public function findAllUpcomingExcluding(User $user) {
        $qb = $this->createQueryBuilder('a');
        $qb->join('a.assignment', 'm')
            ->join('m.scheduledShift', 's')
            ->where($qb->expr()->gte('s.date', ':today'))
            ->andWhere($qb->expr()->neq('m.mentor', ':user'))
            ->join('s.shift', 'h')
            ->orderBy('h.startTime')
            ->orderBy('s.date')
            ->setParameter('today', new \DateTime())
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }
}