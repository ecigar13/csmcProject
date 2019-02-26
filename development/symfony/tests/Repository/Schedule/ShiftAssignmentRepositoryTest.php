<?php

namespace App\Tests\Repository\Schedule;

use App\Entity\Misc\Room;
use App\Entity\Misc\Semester;
use App\Entity\Misc\Subject;
use App\Entity\Schedule\Schedule;
use App\Entity\Schedule\ScheduledShift;
use App\Entity\Schedule\Shift;
use App\Entity\Schedule\ShiftAssignment;
use App\Entity\Session\ScheduledSession;
use App\Entity\Session\SessionTimeSlot;
use App\Entity\Session\SessionType;
use App\Entity\User\User;
use App\Repository\Schedule\ShiftAssignmentRepository;
use App\Tests\Base\PersistenceTest;

/**
 * Tests the shift assignment repository with an artificial semester lasting from July 1st 2018 to July 31st 2018.
 * More details in the documentation of the fields.
 *
 * @package App\Tests\Repository\Schedule
 */
class ShiftAssignmentRepositoryTest extends PersistenceTest
{
    const MORNING = 'morning';
    const AFTERNOON = 'afternoon';

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
     * Tests @see ShiftAssignmentRepository::findForMentorAndSignInTime with a time in which the mentor doesn't have
     * a shift, which should always return null.
     *
     * @dataProvider createFindForTimeOutsideShiftData
     * @param int $mentorIndex
     * @param \DateTime $signInTime
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testFindForTimeOutsideShift(int $mentorIndex, \DateTime $signInTime)
    {
        // We have to do this because the data provider gets called before the setUp method
        $mentor = $this->mentors[$mentorIndex];

        self::assertNull($this->entityManager->getRepository(ShiftAssignment::class)
            ->findForMentorAndSignInTime($mentor, $signInTime));
    }

    public function createFindForTimeOutsideShiftData()
    {
        $shiftLeaderIndex = 0;
        $morningShiftMentorIndex = 1;
        $afternoonShiftMentorIndex = 2;
        $unassignedMentorIndex = 3;

        return array(
            // Invalid mentor and time
            [$unassignedMentorIndex, new \DateTime('2018-07-01 09:59')],
            [$unassignedMentorIndex, new \DateTime('2018-07-01 11:01')],
            [$unassignedMentorIndex, new \DateTime('2018-07-15 13:59')],
            [$unassignedMentorIndex, new \DateTime('2018-07-15 15:01')],
            // Valid mentor but invalid time
            [$shiftLeaderIndex, new \DateTime('2018-07-05 09:59')],
            [$shiftLeaderIndex, new \DateTime('2018-07-05 11:01')],
            [$morningShiftMentorIndex, new \DateTime('2018-07-10 09:59')],
            [$morningShiftMentorIndex, new \DateTime('2018-07-10 11:01')],
            [$afternoonShiftMentorIndex, new \DateTime('2018-07-15 13:59')],
            [$afternoonShiftMentorIndex, new \DateTime('2018-07-15 15:01')],
            //Valid time but invalid mentor
            [$shiftLeaderIndex, new \DateTime('2018-07-20 09:59')],
            [$shiftLeaderIndex, new \DateTime('2018-07-20 11:01')],
            [$shiftLeaderIndex, new \DateTime('2018-07-25 13:59')],
            [$shiftLeaderIndex, new \DateTime('2018-07-25 15:01')],
            // If the mentor signs in right on time or right when the shift ends, no results should be returned
            [$morningShiftMentorIndex, new \DateTime('2018-07-01 10:00')],
            [$morningShiftMentorIndex, new \DateTime('2018-07-01 11:00')],
            [$afternoonShiftMentorIndex, new \DateTime('2018-07-02 14:00')],
            [$afternoonShiftMentorIndex, new \DateTime('2018-07-02 15:00')]
        );
    }

    /**
     * Tests @see ShiftAssignmentRepository::findForMentorAndSignInTime with a combination of mentor and sign-in time
     * that corresponds to a shift, which should always return the correct assignment.
     *
     * @dataProvider createSignInWithinBoundsData
     * @param array $shiftSelector [day, slot]
     * @param int $mentorIndex
     * @param \DateTime $signInTime
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function testFindForMentorAndSignInTime(array $shiftSelector, int $mentorIndex, \DateTime $signInTime)
    {
        // We have to do this because the data provider gets called before the setUpMethod
        $mentor = $this->mentors[$mentorIndex];
        $expected = $this->shiftsByDayAndSlot[$shiftSelector[0]][$shiftSelector[1]];

        $assignment = $this->entityManager->getRepository(ShiftAssignment::class)
            ->findForMentorAndSignInTime($mentor, $signInTime);

        self::assertEquals($mentor, $assignment->getMentor());
        self::assertEquals(new \DateTime($signInTime->format('Y-m-d')), $assignment->getScheduledShift()->getDate());
        self::assertEquals($expected, $assignment->getScheduledShift()->getShift());
    }

    public function createSignInWithinBoundsData()
    {
        $shiftLeaderIndex = 0;
        $morningShiftIndex = 1;
        $afternoonShiftIndex = 2;

        return array(
            // Right after beginning the shift
            [[0, self::MORNING], $shiftLeaderIndex, new \DateTime('2018-07-01 10:00:01')],
            [[0, self::MORNING], $morningShiftIndex, new \DateTime('2018-07-01 10:00:01')],
            [[0, self::AFTERNOON], $shiftLeaderIndex, new \DateTime('2018-07-01 14:00:01')],
            [[0, self::AFTERNOON], $afternoonShiftIndex, new \DateTime('2018-07-01 14:00:01')],
            // In the middle of the shift
            [[3, self::MORNING], $shiftLeaderIndex, new \DateTime('2018-07-18 10:30')],
            [[3, self::MORNING], $morningShiftIndex, new \DateTime('2018-07-18 10:30')],
            [[3, self::AFTERNOON], $shiftLeaderIndex, new \DateTime('2018-07-18 14:30')],
            [[3, self::AFTERNOON], $afternoonShiftIndex, new \DateTime('2018-07-18 14:30')],
            // Right before ending the shift
            [[5, self::MORNING], $shiftLeaderIndex, new \DateTime('2018-07-27 10:59:59')],
            [[5, self::MORNING], $morningShiftIndex, new \DateTime('2018-07-27 10:59:59')],
            [[5, self::AFTERNOON], $shiftLeaderIndex, new \DateTime('2018-07-27 14:59:59')],
            [[5, self::AFTERNOON], $afternoonShiftIndex, new \DateTime('2018-07-27 14:59:59')]
        );
    }

    /**
     * In this case scenario, every even-numbered day of the month has a session in each shift slot.
     *
     * @dataProvider createFindForMentorAndDateData
     * @param int $mentorIndex
     * @param \DateTime $date
     * @param bool $emptyExpected
     */
    public function testFindSessionForMentorAndDate(int $mentorIndex, \DateTime $date, bool $emptyExpected = false)
    {
        $mentor = $this->mentors[$mentorIndex];
        $assignments = $this->entityManager->getRepository(ShiftAssignment::class)
            ->findSessionsForMentorAndDate($mentor, $date);

        if ($emptyExpected) {
            self::assertEmpty($assignments, 'No session assignments should be returned for this date');
        } else {
            self::assertNotEmpty($assignments, 'Session assignments should be returned for this date');
            if ($mentorIndex == 0) {
                self::assertEquals(2, count($assignments),
                    'Two session assignments should be returned for mentor 0');
            } else {
                self::assertEquals(1, count($assignments),
                    'Only one session assignment should be returned for mentors 1 and 2');
            }

            foreach ($assignments as $assignment) {
                self::assertEquals($mentor, $assignment->getMentor());
                self::assertEquals($date, $assignment->getScheduledShift()->getDate());
            }
        }
    }

    public function createFindForMentorAndDateData()
    {
        return array(
            // Mentor 3 is never assigned
            [3, new \DateTime(), true],
            [3, new \DateTime('2018-07-01'), true],
            [3, new \DateTime('2018-07-15'), true],
            // Date with no assignments
            [0, new \DateTime('2018-01-01'), true],
            [1, new \DateTime('2018-08-01'), true],
            [2, new \DateTime('2018-09-01'), true],
            // Odd numbered day (no session)
            [0, new \DateTime('2018-07-01'), true],
            [1, new \DateTime('2018-07-05'), true],
            [2, new \DateTime('2018-07-25'), true],
            // Should return assignments for the session
            [1, new \DateTime('2018-07-02')],
            [2, new \DateTime('2018-07-20')],
            [0, new \DateTime('2018-07-28')]
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
        // Testing will occur over an artificial semester lasting the entirety of July 2018
        $semester = new Semester(Semester::SEASON_DEV, 2018,
            new \DateTime('2018-07-01'), new \DateTime('2018-07-31 23:59:59'), true);

        $this->entityManager->persist($semester);

        $room = new Room('Building', 1, 1, 'Desc', 40, true);
        $this->entityManager->persist($room);

        $schedule = new Schedule($semester);
        $this->entityManager->persist($schedule);

        $subjects = array(
            self::MORNING => new Subject('Morning Subject', 'MS'),
            self::AFTERNOON => new Subject('Afternoon Subject', 'AS')
        );
        $this->entityManager->persist($subjects[self::MORNING]);
        $this->entityManager->persist($subjects[self::AFTERNOON]);

        // Create test objects
        // Mentors
        $this->mentors = array();

        for ($i = 0; $i < 4; $i++) {
            $user = new User('First ' . $i, 'Last ' . $i, 'mxm00000' . $i);
            $this->entityManager->persist($user);
            $this->mentors[] = $user;
        }

        // Shifts
        $this->shiftsByDayAndSlot = array();
        for ($day = 0; $day <= 6; $day++) {
            $dayShifts = array();

            foreach (array(self::MORNING, self::AFTERNOON) as $shiftSlot) {
                if ($shiftSlot == self::MORNING) {
                    $start = new \DateTime('10am');
                    $end = new \DateTime('11am');
                    $subject = $subjects[self::MORNING];
                    $mentor = $this->mentors[1];
                } else {
                    $start = new \DateTime('2pm');
                    $end = new \DateTime('3pm');
                    $subject = $subjects[self::AFTERNOON];
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

        // Create a session
        $sessionType = new SessionType('a', '#000000');
        $this->entityManager->persist($sessionType);
        $session = new ScheduledSession($sessionType, 'session ' . $i, 10, 'desc',
            'student', 'mentor', true, false);
        $session->setDefaults($room, 10, new \DateInterval('PT1H'));

        // Generate scheduled shifts and assignments
        $start_date = $semester->getStartDate();
        $end_date = $semester->getEndDate();
        $interval = new \DateInterval('P1D');

        $period = new \DatePeriod($start_date, $interval, $end_date);

        /** @var \DateTime $date */
        foreach ($period as $date) {
            $day = $date->format('w');

            foreach ($this->shiftsByDayAndSlot[$day] as $shift) {
                $scheduled_shift = new ScheduledShift($schedule, $shift, $date);

                $this->entityManager->persist($scheduled_shift);

                if (((int)$date->format('d')) % 2 == 0) {
                    // Even-numbered days get a session
                    $timeSlot = new SessionTimeSlot($session, $room, $scheduled_shift->getShift()->getStartTime(),
                        $scheduled_shift->getShift()->getEndTime(), 10);
                    $session->addTimeSlot($timeSlot);

                    foreach ($scheduled_shift->getAssignments() as $assignment) {
                        $timeSlot->assign($assignment);
                    }
                }
            }
        }

        $this->entityManager->persist($session);
    }
}
