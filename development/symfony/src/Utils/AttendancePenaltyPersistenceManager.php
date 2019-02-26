<?php


namespace App\Utils;



use App\Entity\Penalty\AbsencePenalty;
use App\Entity\Penalty\AbsenceWithNoticePenalty;
use App\Entity\Penalty\AttendancePenalty;
use App\Entity\Penalty\ClaimShiftBonus;
use App\Entity\Penalty\ShiftCoveredBonus;
use App\Entity\Penalty\TardinessPenalty;
use App\Entity\Occurrence\AbsenceOccurrence;
use App\Entity\Occurrence\AttendanceOccurrence;
use App\Entity\Occurrence\TardinessOccurrence;
use App\Entity\Schedule\Absence;
use App\Entity\Schedule\ShiftAssignment;
use App\Entity\User\User;
use App\Utils\DateTimeService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

/**
 * This is the class responsible for creating and persisting @see AttendancePenalty classes.
 *
 * **Important Note:** @see AttendancePenalty and descendants should *NOT* be directly instantiated/modified. This
 * class should always be used instead. This ensures the model stays consistent.
 *
 * @package App\Entity\Penalty
 * @api Clients are expected to interact with this class and not directly with the classes it manages, see note.
 */
class AttendancePenaltyPersistenceManager
{
    /**
     * These are all instances of the @see TardinessPenalty class. Either:
     *
     * * There are no instances (no penalty is ever applied), or
     *
     * * The instances satisfy these properties:
     *
     *     * The first instance has @see TardinessPenalty::$startingMinutes set as 0.
     *     * The first instance is the only one allowed to have @see TardinessPenalty::$isCumulative set to `true`.
     *     * For each instance, its @see TardinessPenalty::$startingMinutes equals the previous instance's @see TardinessPenalty::$endingMinutes .
     *
     * @var TardinessPenalty[]|null
     */
    private $tardinessPenalties;

    /**
     * There will be either one instance or null.
     *
     * @var AbsencePenalty|null
     */
    private $absenceWithoutNoticePenalty;

    /**
     * These are all instances of the @see AbsenceWithNoticePenalty class. Either:
     *
     * * There are no instances (no penalty is ever applied), or
     *
     * * The instances satisfy these properties:
     *
     *     * They all have @see AbsenceWithNoticePenalty::$isJustified set to `true`.
     *     * The last instance has a `null` value for @see AbsenceWithNoticePenalty::$hoursBefore.
     *     * For each instance, its @see AbsenceWithNoticePenalty::$hoursBefore is greater than the value of the previous instance.
     *
     * @var AbsenceWithNoticePenalty[]|null
     */
    private $justifiedAbsenceWithNoticePenalties;

    /**
     * Similar rules as @see justifiedAbsenceWithNoticePenalties, except @see AbsenceWithNoticePenalty::$isJustified is set to `false`.
     *
     * @var AbsenceWithNoticePenalty[]|null
     */
    private $unjustifiedAbsenceWithNoticePenalties;

    /**
     * There will be one instance or null.
     *
     * @var ShiftCoveredBonus|null
     */
    private $shiftCoveredBonus;

    /**
     * There will be one instance or null.
     *
     * @var ClaimShiftBonus|null
     */
    private $claimShiftBonus;

    /**
     * Should not instantiate this class directly.
     *
     * AbsencePenaltyCommitter constructor.
     * @param TardinessPenalty[]|null $tardinessPenalties
     * @param AbsencePenalty|null $absenceWithoutNoticePenalty
     * @param AbsenceWithNoticePenalty[]|null $justifiedAbsenceWithNoticePenalties
     * @param AbsenceWithNoticePenalty[]|null $unjustifiedAbsenceWithNoticePenalties
     * @param ShiftCoveredBonus|null $shiftCoveredBonus
     * @param ClaimShiftBonus|null $claimShiftBonus
     */
    private function __construct(array $tardinessPenalties = null,
                                 AbsencePenalty $absenceWithoutNoticePenalty = null,
                                 array $justifiedAbsenceWithNoticePenalties = null,
                                 array $unjustifiedAbsenceWithNoticePenalties = null,
                                 ShiftCoveredBonus $shiftCoveredBonus = null,
                                 ClaimShiftBonus $claimShiftBonus = null)
    {
        $this->tardinessPenalties = $tardinessPenalties;
        $this->absenceWithoutNoticePenalty = $absenceWithoutNoticePenalty;
        $this->justifiedAbsenceWithNoticePenalties = $justifiedAbsenceWithNoticePenalties;
        $this->unjustifiedAbsenceWithNoticePenalties = $unjustifiedAbsenceWithNoticePenalties;
        $this->shiftCoveredBonus = $shiftCoveredBonus;
        $this->claimShiftBonus = $claimShiftBonus;
    }

    /**
     * Creates a new instance of this class, loading all relevant instances from the database.
     *
     * @param EntityManager $entityManager
     * @return AttendancePenaltyPersistenceManager
     */
    public static function loadModel(EntityManager $entityManager)
    {
        $tardinessPenalties = $entityManager->getRepository(TardinessPenalty::class)
            ->findBy(array(), array('startingMinutes' => 'ASC'));

        $noNoticePenalty = $entityManager->getRepository(AbsencePenalty::class)
            ->findOneBy(array());

        $justifiedPenalties = self::findAbsenceWithNoticePenalties($entityManager, true);

        $unjustifiedPenalties = self::findAbsenceWithNoticePenalties($entityManager, false);

        $shiftCoveredBonus = $entityManager->getRepository(ShiftCoveredBonus::class)->findOneBy(array());

        $claimShiftBonus = $entityManager->getRepository(ClaimShiftBonus::class)->findOneBy(array());

        return new AttendancePenaltyPersistenceManager($tardinessPenalties, $noNoticePenalty,
            $justifiedPenalties, $unjustifiedPenalties, $shiftCoveredBonus, $claimShiftBonus);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param bool $isJustified
     * @return AbsenceWithNoticePenalty[]|TardinessPenalty[]|array|object[]
     */
    private static function findAbsenceWithNoticePenalties(EntityManagerInterface $entityManager, bool $isJustified)
    {
        // Get all the penalties sorted ascending by hoursBefore with null at the end
        /** @var EntityManager $entityManager */
        $queryBuilder = $entityManager->getRepository(AbsenceWithNoticePenalty::class)->createQueryBuilder('p');
        $query = $queryBuilder
            ->where('p.isJustified = ?1')
            ->addSelect('CASE WHEN p.hoursBefore IS NULL THEN 1 ELSE 0 END AS HIDDEN hoursBefore_is_null')
            ->orderBy('hoursBefore_is_null', 'ASC')
            ->addOrderBy('p.hoursBefore', 'ASC')
            ->setParameter(1, $isJustified)
            ->getQuery();

        $penalties = $query->execute();

        return $penalties ?? null;
    }

    /**
     * Returns the occurrence for a certain amount of tardiness. The returned occurrence can be of the
     * @see TardinessOccurrence or @see AbsenceOccurrence. `null` is possible if no penalty applies.
     *
     * @param User $mentor
     * @param \DateTime $signInTime
     * @param EntityManagerInterface $entityManager
     * @param ShiftAssignment|null $shiftAssignment
     * @param DateTimeService $time
     * @return AttendanceOccurrence|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public static function createOccurrenceForSignInTime(User $mentor, \DateTime $signInTime,
                                                         EntityManagerInterface $entityManager, ShiftAssignment $shiftAssignment = null,
                                                         DateTimeService $time = null)
    {
        if ($shiftAssignment == null) {
            return null;
        }

        $shift = $shiftAssignment->getScheduledShift()->getShift();
        $minutesLate = $shift->calculateTardinessMinutesForSignIn($signInTime);

        /** @var TardinessPenalty $tardinessPenalty */
        /** @var EntityManager $entityManager */
        $tardinessPenalty = $entityManager->getRepository(TardinessPenalty::class)
            ->createQueryBuilder('p')
            // Starting minutes is inclusive
            ->where('p.startingMinutes <= :minutes')
            // Ending minutes is exclusive
            ->andWhere('p.endingMinutes > :minutes')
            ->setParameter('minutes', $minutesLate)
            ->getQuery()
            ->getOneOrNullResult();

        if ($tardinessPenalty != null) {
            return new TardinessOccurrence($mentor, $tardinessPenalty->getPenaltyAmount(), $minutesLate, $time);
        }

        // If no tardiness penalty applies, an absence penalty will apply, if it does not exist, null will be returned
        if ($shiftAssignment->getAbsence() != null) {
            $absencePenalty = self::findPenaltyForNoticeAmount($shiftAssignment->getAbsence(), $shiftAssignment, $entityManager);
        } else {
            $absencePenalty = $entityManager->getRepository(AbsencePenalty::class)
                ->findOneBy(array());
        }

        if ($absencePenalty != null) {
            return new AbsenceOccurrence($mentor, $shiftAssignment->getAssignmentDateTime(), $shiftAssignment->getAbsenceNoticeAmountInHours(),
                $absencePenalty->getPenaltyAmount(), null, $time);
        } else {
            return null;
        }
    }

    /**
     * If notice is given, determines which threshold the notice falls under, and returns
     * the respective penalty object
     *
     * @param Absence $absenceNotice
     * @param ShiftAssignment $assignment
     * @param EntityManagerInterface $entityManager
     * @return \App\Entity\Penalty\AbsencePenalty|\App\Entity\Penalty\AbsenceWithNoticePenalty|null
     */
    public static function findPenaltyForNoticeAmount(Absence $absenceNotice, ShiftAssignment $assignment, EntityManagerInterface $entityManager)
    {
        $penalty = null;

        $noticeTime = $absenceNotice->getCreatedOn();
        $assignmentDateTime = $assignment->getAssignmentDateTime();

        // Shouldn't ever happen, but just in case
        if ($noticeTime >= $assignmentDateTime) {
            return $entityManager->getRepository(AbsencePenalty::class)
                ->findOneBy(array());
        }

        $hoursNotice = $assignment->getAbsenceNoticeAmountInHours();

        // Use unjustified penalties by default
        $penaltyIntervals = self::findAbsenceWithNoticePenalties($entityManager, false);

        // Find the first penalty interval where hours notice is strictly less than interval cutoff
        // since intervals are sorted in increasing order of notice amount (so also in decreasing
        // order of penalty).
        // NOTE: At the cutoff point, for example for a threshold of 24 hours, exactly 24 hours notice
        // is still considered early notice (less penalty). Hence, "strictly less than" is used here.
        foreach ($penaltyIntervals as $penaltyInterval) {
            if (
                $hoursNotice < $penaltyInterval->getHoursBefore()
                || $penaltyInterval->getHoursBefore() == null // last interval
            ) {
                $penalty = $penaltyInterval;
                break;
            }
        }

        return $penalty;
    }

    /**
     * @param array $penaltyAmounts A float array of size `n` of penalty amounts for each interval. A `null` value creates
     * no penalties.
     * @param array $intervalBounds An integer array of size `n` with all interval bounds. The first value cannot be 0.
     * It must be sorted in ascending order and contain no duplicates.
     * @param bool $firstCumulative Whether the first interval should be cumulative.
     */
    public function createTardinessPenalties(array $penaltyAmounts = null, array $intervalBounds = null, bool $firstCumulative = false)
    {
        if ($penaltyAmounts == null || count($penaltyAmounts) == 0) {
            if ($intervalBounds == null || count($intervalBounds) == 0) {
                $this->tardinessPenalties = null;

                return;
            } else {
                // Could just let it slide but it's better to catch potential misuses
                throw new \LogicException('Interval bounds must be empty if penalty amounts is empty');
            }
        }

        if ($intervalBounds == null || count($penaltyAmounts) != count($intervalBounds)) {
            throw new \LogicException('The size of the interval bounds and penalty amounts must be the same');
        }

        if ($intervalBounds[0] == 0) {
            throw new \LogicException('First interval bound cannot be zero');
        }

        // This will throw an exception if they are invalid
        $this->checkValidIntervalBounds($intervalBounds);

        $this->tardinessPenalties = array();

        // Create the first interval setting the cumulative flag
        $this->tardinessPenalties[] = new TardinessPenalty($firstCumulative? 0 : $penaltyAmounts[0], 0, $intervalBounds[0], $firstCumulative);

        // Create the rest of the intervals
        $previousBound = $intervalBounds[0];
        foreach (array_slice($penaltyAmounts, 1, null, true) as $index => $penaltyAmount) {
            $bound = $intervalBounds[$index];

            $this->tardinessPenalties[] = new TardinessPenalty($penaltyAmount, $previousBound, $bound, false);

            $previousBound = $bound;
        }
    }

    /**
     * Throws an exception if:
     *
     * * The elements of the array are not sorted in ascending order, or
     *
     * * There are duplicated elements in the array.
     *
     * @param array $intervalBounds
     */
    private function checkValidIntervalBounds(array $intervalBounds)
    {
        $previousElement = $intervalBounds[0];

        foreach (array_slice($intervalBounds, 1) as $bound) {
            if ($bound <= $previousElement) {
                throw new \LogicException('Interval bounds elements must be in ascending order and contain no duplicates');
            }

            $previousElement = $bound;
        }
    }

    /**
     * @param float $penaltyAmount The penalty amount for an absence without notice or `null` if there is no penalty.
     */
    public function createAbsenceWithoutNoticePenalty(float $penaltyAmount = null)
    {
        if ($penaltyAmount == null) {
            $this->absenceWithoutNoticePenalty = null;
            return;
        }

        $this->absenceWithoutNoticePenalty = new AbsencePenalty($penaltyAmount);
    }

    /**
     * @param array $penaltyAmounts An `n`-sized array of penalty amounts. Can be null if there are no penalties.
     * @param array $intervalBounds An array of size `n-1` of interval bounds. An empty array or `null` can be provided if
     *     there is only one interval. It must be sorted in ascending order and contain no duplicates.
     */
    public function createJustifiedAbsenceWithNoticePenalties(array $penaltyAmounts = null, array $intervalBounds = null)
    {
        $this->justifiedAbsenceWithNoticePenalties =
            $this->createPenaltiesWithNotice(true, $penaltyAmounts, $intervalBounds);
    }

    /**
     * Actually implements penalty with notice creation since both methods are almost the same.
     *
     * @param bool $isJustified
     * @param array $penaltyAmounts
     * @param array $intervalBounds
     * @return AbsenceWithNoticePenalty[]|null
     */
    private function createPenaltiesWithNotice(bool $isJustified, array $penaltyAmounts = null, array $intervalBounds = null)
    {
        if ($penaltyAmounts == null || count($penaltyAmounts) == 0) {
            if ($intervalBounds == null || count($intervalBounds) == 0) {
                return null;
            } else {
                throw new \LogicException('Interval bounds must be null is penalty amounts is null');
            }
        }

        if ($intervalBounds == null) {
            $boundsSize = 0;
            $intervalBounds = array();
        } else {
            $boundsSize = count($intervalBounds);
        }

        if ($boundsSize != count($penaltyAmounts) - 1) {
            throw new \LogicException('The size of the interval bounds must be one less than the size of penalty amounts');
        }

        if ($boundsSize != 0) {
            $this->checkValidIntervalBounds($intervalBounds);
        }

        $penalties = array();

        foreach ($intervalBounds as $index => $bound) {
            $penalties[] = new AbsenceWithNoticePenalty($penaltyAmounts[$index], $isJustified, $bound);
        }

        // The last penalty (the only one if there are no bounds) should have hoursBefore null
        $lastPenaltyAmount = array_values(array_slice($penaltyAmounts, -1))[0];
        $penalties[] = new AbsenceWithNoticePenalty($lastPenaltyAmount, $isJustified, null);

        return $penalties;
    }

    /**
     * @param array $penaltyAmounts An `n`-sized array of penalty amounts. Can be null if there are no penalties.
     * @param array $intervalBounds An array of size `n-1` of interval bounds. An empty array or `null` can be provided if
     *     there is only one interval. It must be sorted in ascending order and contain no duplicates.
     */
    public function createUnjustifiedAbsenceWithNoticePenalties(array $penaltyAmounts = null, array $intervalBounds = null)
    {
        $this->unjustifiedAbsenceWithNoticePenalties =
            $this->createPenaltiesWithNotice(false, $penaltyAmounts, $intervalBounds);
    }

    /**
     * @param float $bonusAmount The bonus amount for getting a shift covered after giving advance notice.
     */
    public function createShiftCoveredBonus(float $bonusAmount = null)
    {
        if ($bonusAmount == null) {
            $this->shiftCoveredBonus = null;
            return;
        }

        $this->shiftCoveredBonus = new ShiftCoveredBonus($bonusAmount);
    }

    /**
     * @param float|null $bonusAmount The bonus amount for covering a shift.
     */
    public function createClaimShiftBonus(float $bonusAmount = null)
    {
        if ($bonusAmount == null) {
            $this->claimShiftBonus = null;
            return;
        }

        $this->claimShiftBonus = new ClaimShiftBonus($bonusAmount);
    }

    /**
     * Persists the attendance penalty model, clearing all database entities and replacing them with the ones contained
     * in this object.
     *
     * @param EntityManager $entityManager
     * @throws \Doctrine\ORM\ORMException
     */
    public function persistModel(EntityManager $entityManager)
    {
        // Clears the whole model
        $entityManager->getRepository(AttendancePenalty::class)
            ->createQueryBuilder('p')
            ->delete()
            ->getQuery()
            ->execute();

        // Persists the whole model
        $this->persistArray($entityManager, $this->tardinessPenalties);
        $this->persistArray($entityManager, $this->justifiedAbsenceWithNoticePenalties);
        $this->persistArray($entityManager, $this->unjustifiedAbsenceWithNoticePenalties);

        if ($this->absenceWithoutNoticePenalty != null) {
            $entityManager->persist($this->absenceWithoutNoticePenalty);
        }

        if ($this->shiftCoveredBonus != null) {
            $entityManager->persist($this->shiftCoveredBonus);
        }

        if ($this->claimShiftBonus != null) {
            $entityManager->persist($this->claimShiftBonus);
        }
    }

    /**
     * @param EntityManager $entityManager
     * @param array $penalties
     * @throws \Doctrine\ORM\ORMException
     */
    private function persistArray(EntityManager $entityManager, array $penalties = null)
    {
        if ($penalties != null) {
            foreach ($penalties as $penalty) {
                $entityManager->persist($penalty);
            }
        }
    }

    /**
     * @return TardinessPenalty[]|null
     */
    public function getTardinessPenalties()
    {
        return $this->tardinessPenalties;
    }

    /**
     * @return AbsencePenalty|null
     */
    public function getAbsenceWithoutNoticePenalty()
    {
        return $this->absenceWithoutNoticePenalty;
    }

    /**
     * @return AbsenceWithNoticePenalty[]|null
     */
    public function getJustifiedAbsenceWithNoticePenalties()
    {
        return $this->justifiedAbsenceWithNoticePenalties;
    }

    /**
     * @return AbsenceWithNoticePenalty[]|null
     */
    public function getUnjustifiedAbsenceWithNoticePenalties()
    {
        return $this->unjustifiedAbsenceWithNoticePenalties;
    }

    /**
     * @return ShiftCoveredBonus|null
     */
    public function getShiftCoveredBonus()
    {
        return $this->shiftCoveredBonus;
    }

    /**
     * @return ClaimShiftBonus|null
     */
    public function getClaimShiftBonus()
    {
        return $this->claimShiftBonus;
    }

    public function getIntervalTable()
    {

        $tardinesspenalties = $this->getTardinessPenalties();
        usort($tardinesspenalties,function($a, $b)
        {
            return $a->getStartingMinutes() - $b->getStartingMinutes();
        });

        $tardinessIntervals = array();
        foreach ($tardinesspenalties as $penalty) {
            array_push($tardinessIntervals,($penalty->getEndingMinutes() - $penalty->getStartingMinutes()));
        }

        $justifiedAbsenceIntervals = array();
        foreach ($this->getJustifiedAbsenceWithNoticePenalties() as $penalty) {
            if($penalty->getHoursBefore())
                array_push($justifiedAbsenceIntervals,$penalty->getHoursBefore());
        }

        $unjustifiedAbsenceIntervals = array();
        foreach ($this->getUnjustifiedAbsenceWithNoticePenalties() as $penalty) {
            if($penalty->getHoursBefore())
                array_push($unjustifiedAbsenceIntervals,$penalty->getHoursBefore());
        }

        $obj =array(
            'tardiness-settings'=>$tardinessIntervals,
            'exc-abs-settings' =>$justifiedAbsenceIntervals,
            'unexc-abs-settings' =>$unjustifiedAbsenceIntervals
        );

        return $obj;
    }

    public function getPointsTable()
    {
        $tardinessPenalties = $this->getTardinessPenalties();
        usort($tardinessPenalties,function($a, $b)
        {
            return $a->getStartingMinutes() - $b->getStartingMinutes();
        });

        $tardinessPoints = array();
        foreach ($tardinessPenalties as $penalty) {
            array_push($tardinessPoints,($penalty->isCumulative() ? "cumulative" : $penalty->getPenaltyAmount()));
        }

        $justifiedAbsencePoints = array();
        foreach ($this->getJustifiedAbsenceWithNoticePenalties() as $penalty) {
            array_push($justifiedAbsencePoints,$penalty->getPenaltyAmount());
        }

        $unjustifiedAbsencePoints = array();
        foreach ($this->getUnjustifiedAbsenceWithNoticePenalties() as $penalty) {
            array_push($unjustifiedAbsencePoints,$penalty->getPenaltyAmount());
        }

        $obj =array(
            'tardiness-settings'=>$tardinessPoints,
            'exc-abs-settings' =>$justifiedAbsencePoints,
            'unexc-abs-settings' =>$unjustifiedAbsencePoints,
            'unnotified-abs' => $this->getAbsenceWithoutNoticePenalty() ? $this->getAbsenceWithoutNoticePenalty()->getPenaltyAmount() : 0,
            'shift-covered' => $this->getShiftCoveredBonus() ? $this->getShiftCoveredBonus()->getPenaltyAmount() : 0,
            'cover-shift' => $this->getClaimShiftBonus() ? $this->getClaimShiftBonus()->getPenaltyAmount() : 0
        );

        return $obj;
    }

}