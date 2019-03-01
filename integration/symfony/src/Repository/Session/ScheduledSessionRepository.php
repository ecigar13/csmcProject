<?php

namespace App\Repository\Session;

use Doctrine\ORM\EntityRepository;

class ScheduledSessionRepository extends EntityRepository {
    public function findAllPastAndCurrent() {
        $qb = $this->createQueryBuilder('s');
        $qb->select('s')
            ->join('s.timeSlots', 't', 'WITH', 't.startTime <= :today')
            ->orderBy('t.startTime');
        $qb->setParameters(array('today' => (new \DateTime())->setTime(23, 59, 59)));

        return $qb->getQuery()->getResult();
    }

    public function findAllPastAndCurrentByInstructor($user) {
        $qb = $this->createQueryBuilder('s');
        $qb->select('s')
            ->join('s.timeSlots', 't', 'WITH', 't.startTime <= :today')
            ->join('s.sections', 'sc', 'WITH', 'sc.instructor = :user')
            ->orderBy('t.startTime');
        $qb->setParameters(
            array(
                'today' => (new \DateTime())->setTime(23, 59, 59),
                'user' => $user
            )
        );

        return $qb->getQuery()->getResult();
    }

    public function findAllFutureAndCurrent() {
        $qb = $this->createQueryBuilder('s');
        $qb->select('s')
            ->join('s.timeSlots', 't', 'WITH', 't.startTime >= :today')
            ->orderBy('t.startTime');
        $qb->setParameters(array('today' => (new \DateTime())->setTime(0, 0, 0)));

        return $qb->getQuery()->getResult();
    }

    public function findAllFutureAndCurrentBySection($section) {
        $qb = $this->createQueryBuilder('s');
        $qb->select('s')
            ->join('s.timeSlots', 't')
            ->where('t.startTime >= :today')
            ->join('s.sections', 'ss')
            ->andWhere('ss = :section')
            ->orderBy('t.startTime');
        $qb->setParameters(array(
            'today' => (new \DateTime())->setTime(0, 0, 0),
            'section' => $section
        ));

        return $qb->getQuery()->getResult();
    }

    public function findBySection($section) {
        $qb = $this->createQueryBuilder('s');
        $qb->select('s')
            ->join('s.sections', 'ss', 'WITH', 'ss = :section');
        $qb->setParameters(array(
            'section' => $section
        ));

        return $qb->getQuery()->getResult();
    }

    public function findAllUnscheduled() {
        $qb = $this->createQueryBuilder('s');
        $qb->select('s')
            ->leftJoin('s.timeSlots', 't')
            ->groupBy('s.id')
            ->having('count(t.id) < s.repeats');

        return $qb->getQuery()->getResult();
    }
}