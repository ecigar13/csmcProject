<?php


namespace App\Repository\Session;


use Doctrine\ORM\EntityRepository;

class TimeSlotRepository extends EntityRepository {
    public function findAllFutureAndCurrent() {
        $qb = $this->createQueryBuilder('t');
        $qb->where($qb->expr()->gte('t.startTime', '?1'))
            ->orWhere($qb->expr()->isNull('t.endTime'))
            ->orderBy('t.startTime', 'ASC');;
        $qb->setParameters(array(1 => ((new \DateTime())->format('y/m/d'))));

        return $qb->getQuery()->getResult();
    }

    public function findAllPastAndCurrent() {
        // TODO implement if necessary
        return null;
    }

    public function findByDay($day) {
        $from = new \DateTime($day->format('Y-m-d') . ' 00:00:00');
        $to = new \DateTime($day->format('Y-m-d') . ' 23:59:59');
        $qb = $this->createQueryBuilder('t');
        $qb->select('t')
            ->where('t.startTime BETWEEN :from AND :to')
            ->orderBy('t.startTime', 'ASC')
            ->setParameters(array(
                'from' => $from,
                'to' => $to
            ));

        return $qb->getQuery()->getResult();
    }

    public function findAllForMonth($month, $year) {
        $qb = $this->createQueryBuilder('t');
        $qb->where('MONTH(t.startTime) = :month')
            ->andWhere('YEAR(t.startTime) = :year')
            ->setParameters(array(
                'month' => $month,
                'year' => $year
            ));
        return $qb->getQuery()->getResult();
    }
}