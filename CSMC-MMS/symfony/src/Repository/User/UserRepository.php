<?php

namespace App\Repository\User;

use App\Entity\Occurrence\Occurrence;
use App\Entity\User\User;
use App\Utils\SwipeManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class UserRepository extends EntityRepository
{
    public function findByRole(string $name)
    {
        $qb = $this->createQueryBuilder('u')
            ->join('u.roles', 'r')
            ->where('r.name = :role')
            ->setParameters(array(
                'role' => $name
            ));
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $cardId
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByCardId($cardId)
    {
        $qb = $this->createQueryBuilder('u');

        if (SwipeManager::isScancodeLegacy($cardId)) {
            $qb->where('u.scancode = :card');
        } else {
            $qb->where('u.cardId = :card');
        }

        $qb->setParameters(array(
            'card' => $cardId
        ));

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns the IDs of the mentors that have pending occurrences.
     *
     * @return string[]
     */
    public function findMentorIdsWithPendingOccurrences()
    {
        $nestedResults = $this->createQueryBuilder('u')
            ->join('u.roles', 'r', Join::WITH, 'r.name = :mentor')
            ->join('App\Entity\Occurrence\Occurrence', 'o', Join::WITH, 'o.status = :status')
            ->select('u.id')
            ->andWhere('o.subject = u')
            ->setParameters(array(
                'status' => Occurrence::STATUS_PENDING,
                // FIXME: string literal should be replaced with a constant
                'mentor' => 'mentor'
            ))
            ->getQuery()
            ->getScalarResult();

        // The returned array will have nesting, so here we convert it into a one-dimensional array of strings
        return array_map('current', $nestedResults);
    }

    /**
     * @return User[]
     */
    public function findMentorsWithEnabledSessionReminders()
    {
        return $this->createQueryBuilder('u')
            ->join('u.notificationPreferences', 'p')
            ->join('u.roles', 'r', Join::WITH, 'r.name = :mentor')
            ->where('p.notifyBeforeSession = 1')
            ->setParameter('mentor', 'mentor')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findMentorsWithEnabledSessionAssignmentNotifications()
    {
        return $this->createQueryBuilder('u')
            ->join('u.notificationPreferences', 'p')
            ->join('u.roles', 'r', Join::WITH, 'r.name = :mentor')
            ->where('p.notifyWhenAssigned = 1')
            ->setParameter('mentor', 'mentor')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns all mentors, sorted for better display on the summary page.
     *
     * @return User[] A list of all mentors, sorted with those with profile modification requests first, then those with
     * pending occurrences, and then all others.
     */
    public function findMentorsForSummaryPage()
    {
        return $this->createQueryBuilder('u')
            ->join('u.roles', 'r', Join::WITH, 'r.name = :mentor')
            ->leftJoin('u.occurrences', 'o', Join::WITH, 'o.status = :pending')
            ->join('u.profile', 'p')
            ->leftJoin('p.modificationRequests', 'req')
            // Create a hidden variable that's 2 if there are modification requests, 1 if there are pending occurrences,
            // and 0 otherwise. Then sort descending, putting the mentors with modification requests first.
            ->addSelect('CASE ' .
                'WHEN COUNT(req) > 0 THEN 2 ' .
                'WHEN COUNT(o) > 0 THEN 1 ' .
                'ELSE 0 END AS HIDDEN has_pending_items')
            ->orderBy('has_pending_items', 'DESC')
            ->addOrderBy('u.firstName', 'ASC')
            // This is required or MySQL will complain about things not being grouped in the fancy select statement
            ->groupBy('u')
            ->setParameters(array(
                'mentor' => 'mentor',
                'pending' => Occurrence::STATUS_PENDING
            ))
            ->getQuery()
            ->getResult();
    }
}