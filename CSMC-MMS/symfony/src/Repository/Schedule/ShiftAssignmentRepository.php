<?php

namespace App\Repository\Schedule;

use App\Entity\Schedule\ShiftAssignment;
use App\Entity\User\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class ShiftAssignmentRepository extends EntityRepository
{
    public function findForTimes(\DateTime $date, \DateTime $time)
    {
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

    public function findForDate(\DateTime $date)
    {

//        $qb = $this->createQueryBuilder('a');
//
//        $qb->join('a.scheduledShift', 'ss')
//            ->where('ss.date = :date')
//            ->setParameters(array(
//                'date' => new \DateTime($date->format('m/d/Y'))
//            ));

        // FIXME: This is a workaround; investigate why this happens:
        // Testing date equality (above) works in the live environment, but not in PHPUnit,
        // so a date-time range spanning the whole day is used instead (below), which works in both

        $dateCopy = new \DateTime($date->format('Y-m-d'));
        $from = new \DateTime($dateCopy->sub(new \DateInterval('P1D'))->format("Y-m-d") . " 23:59:59");
        $to = new \DateTime($date->format("Y-m-d") . " 23:59:59");

        $qb = $this->createQueryBuilder('a');

        $qb->join('a.scheduledShift', 'ss')
            ->where('ss.date BETWEEN :from AND :to')
            ->setParameters(array(
                'from' => $from,
                'to' => $to
            ));

        return $qb->getQuery()->getResult();
    }

    /**
     * @param User $mentor
     * @param \DateTime $signInDateTime
     * @return ShiftAssignment|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findForMentorAndSignInTime(User $mentor, \DateTime $signInDateTime)
    {
        return $this->createQueryBuilder('sa')
            ->join('sa.scheduledShift', 'ss', Join::WITH, 'ss.date = :date')
            ->join('ss.shift', 's')
            ->where('sa.mentor = :mentor')
            ->andWhere('ss.date = :date')
            ->andWhere('s.startTime < :time')
            ->andWhere('s.endTime > :time')
            ->setParameters(array(
                'date' => $signInDateTime->format('Y-m-d'),
                'mentor' => $mentor,
                'time' => $signInDateTime->format('H:i:s')
            ))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds all shift assignments that correspond to the specified date, are assigned to the specified mentor and
     * correspond to a session.
     *
     * @param User $mentor
     * @param \DateTime $date
     * @return ShiftAssignment[]|null
     */
    public function findSessionsForMentorAndDate(User $mentor, \DateTime $date)
    {
        return $this->createQueryBuilder('a')
            ->join('a.scheduledShift', 'ss', Join::WITH, 'ss.date = :date')
            ->where('a.mentor = :mentor')
            ->andWhere('a.session IS NOT null')
            ->setParameters(array(
                'date' => $date->format('Y-m-d'),
                'mentor' => $mentor
            ))
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User $mentor
     * @return ShiftAssignment[]|null
     */
    public function findSessionsForMentorAssignedToday(User $mentor)
    {
        return $this->createQueryBuilder('a')
            ->where('a.mentor = :mentor')
            ->andWhere('a.session IS NOT null')
            ->andWhere('a.assignedOn = :today')
            ->setParameters(array(
                'today' => (new \DateTime())->format('Y-m-d'),
                'mentor' => $mentor
            ))
            ->getQuery()
            ->getResult();
    }
}