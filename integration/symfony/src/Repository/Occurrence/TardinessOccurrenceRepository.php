<?php

namespace App\Repository\Occurrence;

use App\Entity\User\User;
use Doctrine\ORM\EntityRepository;

class TardinessOccurrenceRepository extends EntityRepository
{
    public function findForMentorBetweenDatesWithinAmount(User $mentor, \DateTime $periodStart, \DateTime $periodEnd, $tardinessAmount) {
        $qb = $this->createQueryBuilder('t');
        $qb->where('t.creationDate BETWEEN :from AND :to')
            ->andWhere('t.subject = :mentor')
            ->andWhere('t.tardinessMinutes < :tardiness_amount')
            ->setParameters(array(
                'mentor' => $mentor,
                'from' => $periodStart,
                'to' => $periodEnd,
                'tardiness_amount' => $tardinessAmount
            ));

        return $qb->getQuery()->getResult();
    }
}