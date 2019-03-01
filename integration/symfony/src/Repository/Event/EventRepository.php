<?php

namespace App\Repository\Event;

use Doctrine\ORM\EntityRepository;

class EventRepository extends EntityRepository {
    public function findBetween(\DateTime $start, \DateTime $end) {
        $qb = $this->createQueryBuilder('e');
        // get events
        // between start and end times
        // straddling start time
        // straddling end time
        $qb->where($qb->expr()->andX(
            $qb->expr()->gte('e.startTime', ':start'),
            $qb->expr()->lte('e.endTime', ':end')
        ))->orWhere($qb->expr()->andX(
            $qb->expr()->lte('e.startTime', ':start'),
            $qb->expr()->gte('e.endTime', ':start')
        ))->orWhere($qb->expr()->andX(
            $qb->expr()->lte('e.startTime', ':end'),
            $qb->expr()->gte('e.endTime', ':end')
        ));

        $qb->setParameters(array(
            'start' => $start,
            'end' => $end
        ));

        return $qb->getQuery()->getResult();
    }
}