<?php

namespace App\Tests\Entity\Penalty;

use App\Entity\Misc\Room;
use App\Entity\Misc\Semester;
use App\Entity\Occurrence\AbsenceOccurrence;
use App\Entity\Occurrence\AttendanceOccurrence;
use App\Entity\Occurrence\TardinessOccurrence;
use App\Entity\Penalty\AbsencePenalty;
use App\Entity\Penalty\AbsenceWithNoticePenalty;
use App\Entity\Penalty\AttendancePenalty;
use App\Entity\Penalty\ClaimShiftBonus;
use App\Entity\Penalty\ShiftCoveredBonus;
use App\Entity\Penalty\TardinessPenalty;
use App\Entity\Schedule\Absence;
use App\Entity\Schedule\Schedule;
use App\Entity\Schedule\ScheduledShift;
use App\Entity\Schedule\Shift;
use App\Entity\Schedule\ShiftAssignment;
use App\Entity\User\User;
use App\Tests\Base\PersistenceTest;
use App\Utils\AttendancePenaltyPersistenceManager;
use App\Tests\TestUtils\ReflectionUtils;

/**
 * Tests persistence of @see AttendancePenalty entities.
 * @see AttendancePenaltyCreationTest for creation tests.
 *
 * @package App\Tests\Entity\Penalty
 */
class AttendancePenaltyPersistenceManagerTest extends PersistenceTest
{

    /**
     * @var TardinessPenalty[]
     */
    private $tardinessPenalties;

    /**
     * @var AbsencePenalty
     */
    private $absenceWithoutNoticePenalty;

    /**
     * @var AbsenceWithNoticePenalty[]
     */
    private $justifiedPenalties;

    /**
     * @var AbsenceWithNoticePenalty[]
     */
    private $unjustifiedPenalties;

    /**
     * @var ShiftCoveredBonus
     */
    private $shiftCoveredBonus;

    /**
     * @var ClaimShiftBonus
     */
    private $claimShiftBonus;

    public function testLoadModel()
    {
        $manager = AttendancePenaltyPersistenceManager::loadModel($this->entityManager);

        self::assertEquals($this->tardinessPenalties, $manager->getTardinessPenalties());
        self::assertEquals($this->absenceWithoutNoticePenalty, $manager->getAbsenceWithoutNoticePenalty());
        self::assertEquals($this->justifiedPenalties, $manager->getJustifiedAbsenceWithNoticePenalties());
        self::assertEquals($this->unjustifiedPenalties, $manager->getUnjustifiedAbsenceWithNoticePenalties());
        self::assertEquals($this->shiftCoveredBonus, $manager->getShiftCoveredBonus());
        self::assertEquals($this->claimShiftBonus, $manager->getClaimShiftBonus());
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testClearModel()
    {
        $manager = AttendancePenaltyPersistenceManager::loadModel($this->entityManager);

        $manager->createUnjustifiedAbsenceWithNoticePenalties();
        $manager->createJustifiedAbsenceWithNoticePenalties();
        $manager->createAbsenceWithoutNoticePenalty();
        $manager->createShiftCoveredBonus();
        $manager->createClaimShiftBonus();
        $manager->createTardinessPenalties();

        $manager->persistModel($this->entityManager);
        $this->entityManager->flush();

        self::assertEmpty($this->entityManager->getRepository(AttendancePenalty::class)->findAll());
    }

    // TODO: maybe create testPersist and testUpdate

    /**
     * @dataProvider createOccurrenceForSignInTimeData
     * @param User $mentor
     * @param \DateTime $signInTime
     * @param ShiftAssignment $shiftAssignment
     * @param AttendanceOccurrence|null $expected
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testCreateOccurrenceForSignInTime(User $mentor, \DateTime $signInTime, ShiftAssignment $shiftAssignment = null,
                                                      AttendanceOccurrence $expected = null)
    {
        $actual = AttendancePenaltyPersistenceManager::createOccurrenceForSignInTime($mentor,
            $signInTime, $this->entityManager, $shiftAssignment);

        if ($expected == null) {
            self::assertNull($actual);
            return;
        }

        // We cannot compare the whole object because creation dates will be different
        self::assertInstanceOf(get_class($expected), $actual);
        self::assertEquals($expected->getPoints(), $actual->getPoints());
        if ($expected instanceof TardinessOccurrence) {
            /** @var TardinessOccurrence $actual */
            self::assertEquals($expected->getTardinessMinutes(), $actual->getTardinessMinutes());
        }
    }

    public function createOccurrenceForSignInTimeData()
    {
        $mentor = new User('FName', 'LName', 'fxl000000');
        $semester = new Semester(Semester::SEASON_DEV, 2018, new \DateTime('2018-01-01'), new \DateTime('2018-05-01'), true);
        $room = new Room('Building', 1, 1, 'Desc', 20, true);
        $schedule = new Schedule($semester);

        $shiftAssignment1 = new ShiftAssignment(
            new ScheduledShift($schedule,
                new Shift($schedule, $room, new \DateTime('09:00'), new \DateTime('11:00'), 0),
                new \DateTime()), null, $mentor);

        $shiftAssignment2 = new ShiftAssignment(
            new ScheduledShift($schedule,
                new Shift($schedule, $room, new \DateTime('14:00'), new \DateTime('16:00'), 0),
                new \DateTime()), null, $mentor);

        $shiftAssignment3 = new ShiftAssignment(
            new ScheduledShift($schedule,
                new Shift($schedule, $room, new \DateTime('yesterday 9am'), new \DateTime('yesterday 11am'), 0),
                new \DateTime('yesterday')), null, $mentor);
        // Only one hour in advance
        $absence = new Absence();
        $absence->setReason('Testing');
        $shiftAssignment3->setAbsence($absence);
        ReflectionUtils::assignValueToPrivateProperty($absence, 'createdOn', new \DateTime('yesterday 8am'));

        $shiftAssignment4 = new ShiftAssignment(
            new ScheduledShift($schedule,
                new Shift($schedule, $room, new \DateTime('yesterday 2pm'), new \DateTime('yesterday 4pm'), 0),
                new \DateTime('yesterday')), null, $mentor);
        // A little over 24 hours in advance
        $absence = new Absence();
        $absence->setReason('Testing');
        $shiftAssignment4->setAbsence($absence);
        ReflectionUtils::assignValueToPrivateProperty($absence, 'createdOn', new \DateTime('-2 days 1:59:59pm'));

        $zeroMinOccurrence = new TardinessOccurrence($mentor, 0, 0);
        $fourMinOccurrence = new TardinessOccurrence($mentor, 0, 4);
        $fiveMinOccurrence = new TardinessOccurrence($mentor, 0.5, 5);
        $absenceOccurrence1 = new AbsenceOccurrence($mentor, $shiftAssignment1->getAssignmentDateTime(), $shiftAssignment1->getAbsenceNoticeAmountInHours(), 5);
        $absenceOccurrence2 = new AbsenceOccurrence($mentor, $shiftAssignment2->getAssignmentDateTime(), $shiftAssignment2->getAbsenceNoticeAmountInHours(), 5);

        return array(
            // Null shift should return null
            [$mentor, new \DateTime(), null, null],
            // 0 minutes late should return first penalty
            [$mentor, new \DateTime('09:00:01'), $shiftAssignment1, $zeroMinOccurrence],
            [$mentor, new \DateTime('09:00:59'), $shiftAssignment1, $zeroMinOccurrence],
            [$mentor, new \DateTime('14:00:01'), $shiftAssignment2, $zeroMinOccurrence],
            [$mentor, new \DateTime('14:00:59'), $shiftAssignment2, $zeroMinOccurrence],
            // 5 minutes non-inclusive
            [$mentor, new \DateTime('09:04:59'), $shiftAssignment1, $fourMinOccurrence],
            [$mentor, new \DateTime('14:04:59'), $shiftAssignment2, $fourMinOccurrence],
            // After 5 minutes the second penalty applies
            [$mentor, new \DateTime('09:05:00'), $shiftAssignment1, $fiveMinOccurrence],
            [$mentor, new \DateTime('14:05:00'), $shiftAssignment2, $fiveMinOccurrence],
            // 30 or more minutes should return absence
            [$mentor, new \DateTime('09:30:00'), $shiftAssignment1, $absenceOccurrence1],
            [$mentor, new \DateTime('09:30:01'), $shiftAssignment1, $absenceOccurrence1],
            [$mentor, new \DateTime('10:59:59'), $shiftAssignment1, $absenceOccurrence1],
            [$mentor, new \DateTime('14:30:00'), $shiftAssignment2, $absenceOccurrence2],
            [$mentor, new \DateTime('14:30:01'), $shiftAssignment2, $absenceOccurrence2],
            [$mentor, new \DateTime('15:59:59'), $shiftAssignment2, $absenceOccurrence2],
            // Absences with notice
            [
                $mentor,
                new \DateTime('yesterday 9:40am'), $shiftAssignment3,
                new AbsenceOccurrence($mentor, $shiftAssignment3->getAssignmentDateTime(), $shiftAssignment3->getAbsenceNoticeAmountInHours(), 4)
            ],
            [
                $mentor, new \DateTime('yesterday 2:40pm'),
                $shiftAssignment4,
                new AbsenceOccurrence($mentor, $shiftAssignment4->getAssignmentDateTime(), $shiftAssignment3->getAbsenceNoticeAmountInHours(), 3)
            ]
        );
    }

    /**
     * @inheritdoc
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createTestData()
    {
        // Start the test with the actual model values we have been given
        $this->tardinessPenalties = array(
            new TardinessPenalty(0, 0, 5, true),
            new TardinessPenalty(0.5, 5, 15, false),
            new TardinessPenalty(1, 15, 30, false)
        );

        $this->absenceWithoutNoticePenalty = new AbsencePenalty(5);

        $this->justifiedPenalties = array(
            new AbsenceWithNoticePenalty(2, true, 24),
            new AbsenceWithNoticePenalty(1, true, null)
        );

        $this->unjustifiedPenalties = array(
            new AbsenceWithNoticePenalty(4, false, 24),
            new AbsenceWithNoticePenalty(3, false, null)
        );

        $this->shiftCoveredBonus = new ShiftCoveredBonus(1);

        $this->claimShiftBonus = new ClaimShiftBonus(1);

        // Persist
        $this->persistArray($this->tardinessPenalties);
        $this->entityManager->persist($this->absenceWithoutNoticePenalty);
        $this->persistArray($this->justifiedPenalties);
        $this->persistArray($this->unjustifiedPenalties);
        $this->entityManager->persist($this->shiftCoveredBonus);
        $this->entityManager->persist($this->claimShiftBonus);
    }

    /**
     * @param array $entities
     * @throws \Doctrine\ORM\ORMException
     */
    private function persistArray(array $entities)
    {
        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
        }
    }

}
