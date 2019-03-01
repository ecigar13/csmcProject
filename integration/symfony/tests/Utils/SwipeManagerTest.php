<?php

namespace App\Tests\Utils;

use App\Entity\Misc\Room;
use App\Entity\Misc\Semester;
use App\Entity\Misc\Subject;
use App\Entity\Occurrence\AbsenceOccurrence;
use App\Entity\Occurrence\AttendanceOccurrence;
use App\Entity\Occurrence\TardinessOccurrence;
use App\Entity\Penalty\AbsencePenalty;
use App\Entity\Penalty\AbsenceWithNoticePenalty;
use App\Entity\Penalty\ClaimShiftBonus;
use App\Entity\Penalty\ShiftCoveredBonus;
use App\Entity\Penalty\TardinessPenalty;
use App\Entity\Schedule\Schedule;
use App\Entity\Schedule\ScheduledShift;
use App\Entity\Schedule\Shift;
use App\Entity\User\Role;
use App\Entity\User\User;
use App\Tests\Base\PersistenceTest;
use App\Tests\TestUtils\MockDateTimeService;

/**
 * Tests the swipe manager with an artificial semester lasting from July 1st 2018 to July 31st 2018. More details
 * in the documentation of the fields.
 *
 * @package App\Tests\Utils
 */
class SwipeManagerTest extends PersistenceTest
{
    const MORNING_SLOT = 'morning';
    const AFTERNOON_SLOT = 'afternoon';

    /**
     * * Mentor 0 is always shift leader
     * * Mentor 1 is assigned to the morning shift
     * * Mentor 2 is assigned to the afternoon shift
     * * Mentor 3 is never assigned
     *
     * @var User[]
     */
    private $mentors;

    /**
     * The array has length 7, one for every day of the week, from 0 (Sunday) to 6 (Saturday). Each day has two shift
     * slots, with keys `morning` and `afternoon`:
     *
     *   * `morning` shift goes from 10 am to 11 am.
     *   * `afternoon` shift goes from 2 pm to 3 pm.
     *
     * @var array
     */
    private $shiftsByDayAndSlot;

    /**
     * Tests tardiness calculation.
     *
     * @dataProvider createTardinessData
     * @param int $mentorIndex
     * @param string $swipeInTimeString
     * @param string|null $expectedOccurrenceClass
     * @param float $expectedPoints
     * @param int $expectedTardinessMinutes
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testCalculateTardiness(int $mentorIndex, string $swipeInTimeString, string $expectedOccurrenceClass = null,
                                           float $expectedPoints = null, int $expectedTardinessMinutes = null)
    {
        // Make sure no occurrences exist
        if ($this->entityManager->getRepository(AttendanceOccurrence::class)->count(array()) != 0) {
            throw new \LogicException('The attendance occurrence database must be clean');
        }

        MockDateTimeService::$now = $swipeInTimeString;

        $swipeManager = self::$kernel->getContainer()->get('test.swipe_manager');
        $subject = $this->mentors[$mentorIndex];
        $swipeManager->walkInSwipe($subject->getCardId());

        $occurrences = $this->entityManager->getRepository(AttendanceOccurrence::class)->findAll();

        if ($expectedOccurrenceClass == null) {
            self::assertEquals(0, count($occurrences), 'No occurrences should be created in this case');
        } else {
            self::assertEquals(1, count($occurrences), 'Only one occurrence should be created');
            $this->assertCorrectOccurrence($swipeInTimeString, $expectedOccurrenceClass, $subject, $expectedPoints, $expectedTardinessMinutes, $occurrences[0]);
        }

        // So that it doesn't interfere with other tests
        MockDateTimeService::$now = null;
    }

    private function assertCorrectOccurrence(string $swipeInTimeString, string $expectedOccurrenceClass, User $expectedSubject,
                                             float $expectedPoints, int $expectedTardinessMinutes, AttendanceOccurrence $actual)
    {
        self::assertEquals($expectedOccurrenceClass, get_class($actual));
        self::assertEquals($expectedSubject, $actual->getSubject());
        self::assertEquals($expectedPoints, $actual->getPoints());
        // IMPORTANT: if this somehow starts failing because it's taking too long to process the request just increase the delta
        self::assertEquals((new \DateTime($swipeInTimeString))->getTimestamp(), $actual->getCreationDate()->getTimestamp(),
            'Occurrence creation date should be close to the swipe-in time', 0);

        if ($actual instanceof TardinessOccurrence) {
            self::assertEquals($expectedTardinessMinutes, $actual->getTardinessMinutes());
        }
    }

    /**
     * @return array
     */
    public function createTardinessData()
    {
        return array(
            // Signing in before shift or right at the time of start or end
            [1, '2018-07-01 9:59:00', null, null, null],
            [1, '2018-07-01 9:59:59', null, null, null],
            [1, '2018-07-01 10:00:00', null, null, null],
            [1, '2018-07-01 11:00:00', null, null, null],
            [1, '2018-07-01 11:00:01', null, null, null],
            // Signing in and getting the first penalty
            [1, '2018-07-01 10:00:01', TardinessOccurrence::class, 0, 0],
            [1, '2018-07-01 10:00:59', TardinessOccurrence::class, 0, 0],
            [1, '2018-07-01 10:04:59', TardinessOccurrence::class, 0, 4],
            // Second penalty
            [1, '2018-07-01 10:05:00', TardinessOccurrence::class, 0.5, 5],
            // Right before counting as an absence
            [1, '2018-07-01 10:29:29', TardinessOccurrence::class, 1, 29],
            // Absence
            [1, '2018-07-01 10:30:00', AbsenceOccurrence::class, 5, 0],
            [1, '2018-07-01 10:59:59', AbsenceOccurrence::class, 5, 0],
            // No shifts assigned
            [3, '2018-07-01 10:30:00', null, null, null]
        );
    }

    /**
     * @inheritdoc
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    protected function createTestData()
    {
        // Create required objects
        // Testing will occur over an artificial semester lasting the entirety of January 2018
        $semester = new Semester(Semester::SEASON_DEV, 2018,
            new \DateTime('2018-07-01'), new \DateTime('2018-07-31 23:59:59'), true);

        $this->entityManager->persist($semester);

        $room = new Room('Building', 1, 1, 'Desc', 40, true);
        $this->entityManager->persist($room);

        $schedule = new Schedule($semester);
        $this->entityManager->persist($schedule);

        $subjects = array(
            self::MORNING_SLOT => new Subject('Morning Subject', 'MS'),
            self::AFTERNOON_SLOT => new Subject('Afternoon Subject', 'AS')
        );
        $this->entityManager->persist($subjects[self::MORNING_SLOT]);
        $this->entityManager->persist($subjects[self::AFTERNOON_SLOT]);

        $mentorRole = new Role('mentor');
        $this->entityManager->persist($mentorRole);

        // Create test objects
        // Mentors
        $this->mentors = array();

        for ($i = 0; $i < 4; $i++) {
            $id_pad = str_pad($i, 6, '0', STR_PAD_LEFT);
            $user = new User('First ' . $i, 'Last ' . $i, 'mxm' . $id_pad);
            $user->addRole($mentorRole);
            $user->updateCardId($id_pad . ':test', false);
            $this->entityManager->persist($user);
            $this->mentors[] = $user;
        }

        // Shifts
        $this->shiftsByDayAndSlot = array();
        for ($day = 0; $day <= 6; $day++) {
            $dayShifts = array();

            foreach (array(self::MORNING_SLOT, self::AFTERNOON_SLOT) as $shiftSlot) {
                if ($shiftSlot == self::MORNING_SLOT) {
                    $start = new \DateTime('10am');
                    $end = new \DateTime('11am');
                    $subject = $subjects[self::MORNING_SLOT];
                    $mentor = $this->mentors[1];
                } else {
                    $start = new \DateTime('2pm');
                    $end = new \DateTime('3pm');
                    $subject = $subjects[self::AFTERNOON_SLOT];
                    $mentor = $this->mentors[2];
                }

                $newShift = new Shift($schedule, $room, $start, $end, $day);

                $newShift->addSubject($subject, 3);
                $newShift->assignShiftLeader($this->mentors[0]);
                $newShift->addMentor($subject, $mentor);

                $this->entityManager->persist($newShift);

                $dayShifts[$shiftSlot] = $newShift;
            }

            $this->shiftsByDayAndSlot[$day] = $dayShifts;
        }

        // Generate scheduled shifts and assignments
        $start_date = $semester->getStartDate();
        $end_date = $semester->getEndDate();
        $interval = new \DateInterval('P1D');

        $period = new \DatePeriod($start_date, $interval, $end_date);

        foreach ($period as $date) {
            $day = $date->format('w');

            foreach ($this->shiftsByDayAndSlot[$day] as $shift) {
                $scheduled_shift = new ScheduledShift($schedule, $shift, $date);

                $this->entityManager->persist($scheduled_shift);
            }
        }

        // We also need penalties
        $tardinessPenalties = array(
            new TardinessPenalty(0, 0, 5, true),
            new TardinessPenalty(0.5, 5, 15, false),
            new TardinessPenalty(1, 15, 30, false)
        );

        $absenceWithoutNoticePenalty = new AbsencePenalty(5);

        $justifiedPenalties = array(
            new AbsenceWithNoticePenalty(2, true, 24),
            new AbsenceWithNoticePenalty(1, true, null)
        );

        $unjustifiedPenalties = array(
            new AbsenceWithNoticePenalty(4, false, 24),
            new AbsenceWithNoticePenalty(3, false, null)
        );

        $shiftCoveredBonus = new ShiftCoveredBonus(1);

        $claimShiftBonus = new ClaimShiftBonus(1);

        // Persist
        $this->persistArray($tardinessPenalties);
        $this->entityManager->persist($absenceWithoutNoticePenalty);
        $this->persistArray($justifiedPenalties);
        $this->persistArray($unjustifiedPenalties);
        $this->entityManager->persist($shiftCoveredBonus);
        $this->entityManager->persist($claimShiftBonus);
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
