<?php

namespace App\Repository\Session;

use Doctrine\ORM\EntityRepository;

class QuizRepository extends EntityRepository {
    public function findAllFutureAndCurrent() {
        $qb = $this->createQueryBuilder('q');
        $qb->join('q.timeSlot', 't')
            ->where($qb->expr()->gte('t.startTime', ':today'))
            ->orWhere($qb->expr()->isNull('t.endTime'))
            ->orderBy('t.startTime', 'ASC');
        $qb->setParameters(array('today' => (new \DateTime())->format('y/m/d')));

        return $qb->getQuery()->getResult();
    }

    public function findAllPastAndCurrent() {
        $qb = $this->createQueryBuilder('q');
        $qb->join('q.timeSlot', 't')
            ->where($qb->expr()->lte('t.startTime', ':today'))
            ->orderBy('t.startTime', 'DESC');
        $qb->setParameters(array('today' => (new \DateTime())->format('y/m/d')));

        return $qb->getQuery()->getResult();
    }
    //
    // public function findAllPastAndCurrentByInstructor($user) {
    //     $qb = $this->createQueryBuilder('q');
    //     $qb->join('q.sections', 's', 'WITH', 's.instructor = :user')
    //         ->where($qb->expr()->lte('q.startDate', ':today'))
    //         ->orderBy('q.startDate', 'DESC');
    //     $qb->setParameters(
    //         array(
    //             'user' => $user,
    //             'today' => (new \DateTime())->format('y/m/d')
    //         )
    //     );
    //
    //     return $qb->getQuery()->getResult();
    // }
    //
    public function findByDay($day) {
        $qb = $this->createQueryBuilder('q');
        $qb->join('q.timeSlot', 't')
            ->where($qb->expr()->lte('t.startTime', ':day'))
            ->andWhere($qb->expr()->gte('t.endTime', ':day'))
            ->setParameter('day', $day);

        return $qb->getQuery()->getResult();
    }

    //
    public function findAllFutureAndCurrentBySection($section) {
        // $qb = $this->createQueryBuilder('q');
        // $qb->join('q.sections', 's', 'WITH', 's = :section')
        //     ->where($qb->expr()->gte('q.endDate', ':today'))
        //     ->orderBy('q.startDate', 'ASC');;
        // $qb->setParameters(array(
        //     'today' => (new \DateTime())->setTime(0, 0, 0),
        //     'section' => $section
        // ));
        //
        // return $qb->getQuery()->getResult();

        $qb = $this->createQueryBuilder('q');
        $qb->join('q.timeSlot', 't')
            ->where($qb->expr()->gte('t.startTime', ':today'))
            ->join('q.sections', 's', 'WITH', 's = :section')
            ->orWhere($qb->expr()->isNull('t.endTime'))
            ->orderBy('t.startTime', 'ASC');
        $qb->setParameters(array(
            'today' => (new \DateTime())->format('y/m/d'),
            'section' => $section
        ));

        return $qb->getQuery()->getResult();
    }
    //
    // public function findBySection($section) {
    //     $qb = $this->createQueryBuilder('q');
    //     $qb->join('q.sections', 's', 'WITH', 's = :section');
    //     $qb->setParameters(array(
    //         'section' => $section
    //     ));
    //
    //     return $qb->getQuery()->getResult();
    // }
    //
    // public function findAllForMonth($month, $year) {
    //     $qb = $this->createQueryBuilder('q');
    //     $qb->where($qb->expr()->andX('MONTH(q.startDate) = :month', 'YEAR(q.startDate) = :year'))
    //         ->orWhere($qb->expr()->andX('MONTH(q.endDate) = :month', 'YEAR(q.endDate) = :year'))
    //         ->setParameters(array(
    //             'month' => $month,
    //             'year' => $year
    //         ));
    //     return $qb->getQuery()->getResult();
    // }
}