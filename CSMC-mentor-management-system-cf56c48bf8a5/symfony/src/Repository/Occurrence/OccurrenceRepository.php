<?php


namespace App\Repository\Occurrence;


use App\Entity\Occurrence\Occurrence;
use App\Entity\Occurrence\TardinessOccurrence;
use App\Entity\User\User;
use Doctrine\ORM\EntityRepository;

class OccurrenceRepository extends EntityRepository
{
    /**
     * Finds pending occurrences, *but* ignores @see TardinessOccurrence with a score of 0 points (they are cumulative).
     *
     * @param User|null $mentor If provided, will return occurrences only for that mentor
     * @return Occurrence[]
     */
    public function findPendingOccurrencesForDisplaying(User $mentor = null)
    {
        return $this->findOccurrencesForDisplaying(true, $mentor);
    }

    /**
     * Finds closed occurrences, *but* ignores @see TardinessOccurrence with a score of 0 points (they are cumulative).
     *
     * @param User|null $mentor If provided, will return occurrences only for that mentor
     * @return Occurrence[]
     */
    public function findClosedOccurrencesForDisplaying(User $mentor = null)
    {
        return $this->findOccurrencesForDisplaying(false, $mentor);
    }

    /**
     * Finds pending or closed occurrences, *but* ignores @see TardinessOccurrence with a score of 0 points
     * (they are cumulative).
     *
     * @param bool $pending If `true`, returns pending occurrences, otherwise returns closed occurrences.
     * @param User $mentor If provided, returns only occurrences for that mentor
     * @return Occurrence[]
     */
    private function findOccurrencesForDisplaying(bool $pending, User $mentor = null)
    {
        $operator = $pending ? '=' : '!=';

        $queryBuilder = $this->createQueryBuilder('o')
            ->where("o.status $operator :pending")
            // Must put cumulative ones here explicitly because
            ->andWhere('o NOT INSTANCE OF App\Entity\Occurrence\TardinessOccurrence OR '
            . 'o INSTANCE OF App\Entity\Occurrence\CumulativeTardinessOccurrence OR o.points != 0')
            ->setParameter('pending', Occurrence::STATUS_PENDING);

        if ($mentor != null) {
            $queryBuilder
                ->andWhere('o.subject = :mentor')
                ->setParameter('mentor', $mentor);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPendingOccurrencesCount()
    {
        $qb = $this->createQueryBuilder('o');

        return $qb->select($qb->expr()->count('o.id'))
            ->where('o.status = :pending')
            ->andWhere('o NOT INSTANCE OF App\Entity\Occurrence\TardinessOccurrence OR '
            . 'o INSTANCE OF App\Entity\Occurrence\CumulativeTardinessOccurrence OR o.points != 0')
            ->setParameter('pending', Occurrence::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

}