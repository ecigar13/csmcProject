<?php

namespace App\Tests\Entity\Penalty;

use App\Command\DetectAbsencesCommand;
use App\Entity\Misc\Room;
use App\Entity\Misc\Semester;
use App\Entity\Misc\Subject;
use App\Entity\Occurrence\AbsenceOccurrence;
use App\Entity\Occurrence\ClaimShiftOccurrence;
use App\Entity\Occurrence\ShiftCoveredOccurrence;
use App\Entity\Schedule\Absence;
use App\Entity\Schedule\Schedule;
use App\Entity\Schedule\ScheduledShift;
use App\Entity\Schedule\Shift;
use App\Entity\Schedule\ShiftAssignment;
use App\Entity\Schedule\Timesheet;
use App\Entity\User\Role;
use App\Entity\User\User;
use App\Tests\Base\PersistenceTest;
use App\Utils\AttendancePenaltyPersistenceManager;
use Deployer\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AbsencePenaltyDetectionTest extends PersistenceTest
{
    /**
     * @var User[]
     */
    private $mentors;

    /**
     * @var Semester
     */
    private $semester;

    /**
     * @var Schedule
     */
    private $schedule;

    /**
     * @var Subject
     */
    private $subject;

    /**
     * @var Room
     */
    private $room;

    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * @param string $date
     */
    protected function executeDetectAbsences($date = null) {
        // Initialize instance of the command under test
        $application = new Application();
        $application->add(new DetectAbsencesCommand($this->entityManager));
        $command = $application->find("app:detect-absences");
        $this->commandTester = new CommandTester($command);
        $this->commandTester->execute(
            array(
                'date' => $date
            ),
            array(
                'interactive' => false
            )
        );
    }

    // -----------------------------------------------------------------------
    // Tests
    // -----------------------------------------------------------------------

    /**
     * Notes:
     * 1. The 10:00 AM - 11:30 AM shift on 10-12-2018 is arbitrary
     * 2. Also checks that detection still works for shift leaders
     *
     * @dataProvider createTimesheetForOneShiftData
     * @param $timeIn
     * @param $timeOut
     * @param $expectedAbsent
     * @param $isShiftLeader
     */
    public function testForOneShift($timeIn, $timeOut, $expectedAbsent, $isShiftLeader) {
        $date = '2018-10-12';
        $testMentor = $this->mentors[0];

        if ($isShiftLeader)
            $this->createShift('10am', '11:30am', $date, [], $testMentor);
        else
            $this->createShift('10am', '11:30am', $date, [$testMentor]);

        if ($timeIn && $timeOut) {
            $dateTimeIn = $date . ' ' . $timeIn;
            $dateTimeOut = $date . ' ' . $timeOut;
            $this->createTimesheetForTimes(
                $testMentor,
                new \DateTime($dateTimeIn),
                new \DateTime($dateTimeOut)
            );
        } else if ($timeIn) {
            $dateTimeIn = $date . ' ' . $timeIn;
            $this->createTimesheetForTimes(
                $testMentor,
                new \DateTime($dateTimeIn),
                null
            );
        }

        $this->entityManager->flush();

        // Must clear because we updated the database directly, otherwise the timesheets will not be reflected
        $this->entityManager->clear();

        $this->executeDetectAbsences($date);

        /** @var AbsenceOccurrence[] $absenceOccurrences */
        $absenceOccurrences = $this->entityManager
            ->getRepository(AbsenceOccurrence::class)
            ->findAll();

        if ($expectedAbsent) {
            self::assertEquals(1, count($absenceOccurrences));
            $occurrence = $absenceOccurrences[0];
            self::assertEquals(-5, $occurrence->getPoints());
            self::assertEquals(new \DateTime($date . ' 10:00:00'), $occurrence->getShiftDateTime());
            self::assertEquals($testMentor->getId(), $occurrence->getSubject()->getId());
        } else {
            self::assertEquals(0, count($absenceOccurrences));
        }

        // Make sure shift covered and claim shift don't get created when no substitutes have been made
        $shiftCoveredOccurrences = $this->entityManager->getRepository(ShiftCoveredOccurrence::class)->findAll();
        $claimShiftOccurrences = $this->entityManager->getRepository(ClaimShiftOccurrence::class)->findAll();
        self::assertEquals(0, count($shiftCoveredOccurrences));
        self::assertEquals(0, count($claimShiftOccurrences));
    }

    public function createTimesheetForOneShiftData() {
        return array(
            // ---- Present ----

            // Signed in early
            array('9:59', '11:31', false, false),
            array('9:59', '11:31', false, true),

            // Signed in right on time
            array('10:00', '11:31', false, false),
            array('10:00', '11:31', false, true),

            // Signed in during shift
            array('10:08', '11:25', false, false),
            array('10:08', '11:25', false, true),

            // Signed in early and left early
            array('9:59', '11:00', false, false),
            array('9:59', '11:00', false, true),

            // No sign-out
            array('9:59', '', false, true),
            array('9:59', '', false, false),


            // ---- Absent ----

            // Signed in early but signed out before shift even started
            array('8:00', '9:59', true, false),
            array('8:00', '9:59', true, true),

            // Signed in once shift ends
            array('11:30', '12:55', true, false),
            array('11:30', '12:55', true, true),

            // Signed in after shift ended
            array('11:31', '12:55', true, false),
            array('11:31', '12:55', true, true),

            // No sign-in
            array('', '', true, false),
            array('', '', true, true),

            // No sign-out
            array('11:30', '', true, true),
            array('11:30', '', true, false),
        );
    }

    /**
     * @dataProvider createTimesheetForContiguousShiftsData
     * @param $timeIn
     * @param $timeOut
     * @param $expectedAbsences
     */
    public function testForContiguousShifts($timeIn, $timeOut, $expectedAbsences) {
        $date = '2018-10-12';
        $testMentor = $this->mentors[0];

        $this->createShift('10am', '11:30am', $date, [$testMentor]);
        $this->createShift('11:30am', '1:00pm', $date, [$testMentor]);

        if ($timeIn && $timeOut) {
            $dateTimeIn = $date . ' ' . $timeIn;
            $dateTimeOut = $date . ' ' . $timeOut;
            $this->createTimesheetForTimes(
                $testMentor,
                new \DateTime($dateTimeIn),
                new \DateTime($dateTimeOut)
            );
        }

        $this->entityManager->flush();

        // Must clear because we updated the database directly, otherwise the timesheets will not be reflected
        $this->entityManager->clear();

        $this->executeDetectAbsences($date);

        /** @var AbsenceOccurrence[] $absenceOccurrences */
        $absenceOccurrences = $this->entityManager
            ->getRepository(AbsenceOccurrence::class)
            ->findAll();

        self::assertEquals(count($expectedAbsences), count($absenceOccurrences));

        foreach ($expectedAbsences as $expectedAbsenceTime) {
            /** @var AbsenceOccurrence[] $foundAbsenceOccurrences */
            $foundAbsenceOccurrences = $this->entityManager->getRepository(AbsenceOccurrence::class)
                ->findByShiftDateTime(new \DateTime($date . ' ' . $expectedAbsenceTime));
            self::assertEquals(1, count($foundAbsenceOccurrences));
            $absenceOccurrence = $foundAbsenceOccurrences[0];
            self::assertEquals(-5, $absenceOccurrence->getPoints());
            self::assertEquals($testMentor->getId(), $absenceOccurrence->getSubject()->getId());
        }
    }

    public function createTimesheetForContiguousShiftsData() {
        return array(
            // Present for both
            array('10:01', '13:00', []),

            // Absent for first
            array('11:35', '13:00', ['10:00:00']),

            // Absent for second
            array('9:59', '11:25', ['11:30:00']),

            // Absent for first, but signed in early for second
            // NOTE: Absence is not detected here, but at swipe time!
            array('11:29', '13:00', []),

            // Absent for both, but signed in early for next shift
            // NOTE: Second absence is not detected here, but at swipe time!
            array('12:55', '14:24', ['10:00:00']),

            /*
             * FIXME: What should we do about this scenario?
             * Pretty much absent for second, but could be viewed as:
             * (1) signed out late after first shift
             *   -or-
             * (2) left super early for second shift.
             *
             * We don't have detection rules for leaving early; should be reported manually as
             * an occurrence, instead, e.g. by the shift leader.
             */
            array('9:59', '11:31', []),

            // Absent for both (signed in too late)
            array('13:00', '14:30', ['10:00:00', '11:30:00']),

            // Absent for both (no sign in)
            array('', '', ['10:00:00', '11:30:00'])
        );
    }

    /**
     * @dataProvider createTimesheetsForHoleInSchedule
     * @param array $timesheets
     * @param $expectedAbsences
     */
    public function testForHoleInSchedule($timesheets, $expectedAbsences) {
        $date = '2018-10-13';
        $testMentor = $this->mentors[0];

        // Probably not necessary, but this is so every shift has someone assigned
        $otherMentor = $this->mentors[1];

        $this->createShift('10am', '11:30am', $date, [$otherMentor, $testMentor]);
        $this->createShift('11:30am', '1:00pm', $date, [$otherMentor]);
        $this->createShift('1:00pm', '2:30pm', $date, [$otherMentor, $testMentor]);

        // Other mentor is there the whole time
        $this->createTimesheetForTimes(
            $otherMentor,
            new \DateTime($date . ' 9:59'),
            new \DateTime($date . ' 14:31')
        );

        foreach ($timesheets as $timesheet) {
            $dateTimeIn = $date . ' ' . $timesheet['in'];
            $dateTimeOut = $date . ' ' . $timesheet['out'];
            $this->createTimesheetForTimes(
                $testMentor,
                new \DateTime($dateTimeIn),
                new \DateTime($dateTimeOut)
            );
        }

        $this->entityManager->flush();

        // Must clear because we updated the database directly, otherwise the timesheets will not be reflected
        $this->entityManager->clear();

        $this->executeDetectAbsences($date);

        /** @var AbsenceOccurrence[] $absenceOccurrences */
        $absenceOccurrences = $this->entityManager
            ->getRepository(AbsenceOccurrence::class)
            ->findAll();

        self::assertEquals(count($expectedAbsences), count($absenceOccurrences));

        foreach ($expectedAbsences as $expectedAbsenceTime) {
            /** @var AbsenceOccurrence[] $foundAbsenceOccurrences */
            $foundAbsenceOccurrences = $this->entityManager->getRepository(AbsenceOccurrence::class)
                ->findByShiftDateTime(new \DateTime($date . ' ' . $expectedAbsenceTime));
            self::assertEquals(1, count($foundAbsenceOccurrences));
            $absenceOccurrence = $foundAbsenceOccurrences[0];
            self::assertEquals(-5, $absenceOccurrence->getPoints());
            self::assertEquals($testMentor->getId(), $absenceOccurrence->getSubject()->getId());
        }
    }

    public function createTimesheetsForHoleInSchedule() {
        return array(
            // Present for both
            array(
                [
                    ['in' => '10:00', 'out' => '11:29'],
                    ['in' => '13:00', 'out' => '14:30']
                ],
                []
            ),

            // Absent for first
            array(
                [
                    ['in' => '12:55', 'out' => '14:30']
                ],
                ['10:00:00']
            ),

            // Absent for second
            array(
                [
                    ['in' => '10:00', 'out' => '11:30']
                ],
                ['13:00:00']
            ),

            /*
             * FIXME: What should we do about this scenario?
             * Present for both, but stayed for middle shift (hole in schedule).
             * Is this a problem?
             */
            array(
                [
                    ['in' => '10:00', 'out' => '14:30']
                ],
                []
            ),

            // Absent for both, but signed in early for next shift
            // NOTE: Second absence is not detected here, but at swipe time!
            array(
                [
                    ['in' => '14:25', 'out' => '16:00']
                ],
                ['10:00:00']
            ),

            // Absent for both (signed in too late)
            array(
                [
                    ['in' => '14:31', 'out' => '16:00']
                ],
                ['10:00:00', '13:00:00']
            ),

            // Absent for both (no sign in)
            array([], ['10:00:00', '13:00:00'])
        );
    }

    /**
     * @dataProvider createTimesheetsForMultipleMentors
     * @param array $timesheets
     * @param $expectedAbsences
     */
    public function testForMultipleMentors($timesheets, $expectedAbsences) {
        $date = '2018-10-12';

        $this->createShift('10am', '11:30am', $date, $this->mentors);

        foreach ($timesheets as $timesheet) {
            $dateTimeIn = $date . ' ' . $timesheet['in'];
            $dateTimeOut = $date . ' ' . $timesheet['out'];
            $this->createTimesheetForTimes(
                $this->mentors[$timesheet['mentorIdx']],
                new \DateTime($dateTimeIn),
                new \DateTime($dateTimeOut)
            );
        }

        $this->entityManager->flush();

        // Must clear because we updated the database directly, otherwise the timesheets will not be reflected
        $this->entityManager->clear();

        $this->executeDetectAbsences($date);

        /** @var AbsenceOccurrence[] $absenceOccurrences */
        $absenceOccurrences = $this->entityManager
            ->getRepository(AbsenceOccurrence::class)
            ->findAll();

        self::assertEquals(count($expectedAbsences), count($absenceOccurrences));

        foreach ($expectedAbsences as $expectedAbsence) {
            /** @var AbsenceOccurrence[] $foundAbsenceOccurrences */
            $foundAbsenceOccurrences = $this->entityManager->getRepository(AbsenceOccurrence::class)
                ->findBySubject($this->mentors[$expectedAbsence['mentorIdx']]);
            self::assertEquals(1, count($foundAbsenceOccurrences));
            $absenceOccurrence = $foundAbsenceOccurrences[0];
            self::assertEquals(-5, $absenceOccurrence->getPoints());
            self::assertEquals($this->mentors[$expectedAbsence['mentorIdx']]->getId(), $absenceOccurrence->getSubject()->getId());
        }
    }

    public function createTimesheetsForMultipleMentors() {
        return array(
            // All present
            array(
                [
                    ['mentorIdx' => 0, 'in' => '9:59', 'out' => '11:29'],
                    ['mentorIdx' => 1, 'in' => '10:00', 'out' => '11:29'],
                    ['mentorIdx' => 2, 'in' => '10:01', 'out' => '11:29']
                ],
                []
            ),

            // One absent
            array(
                [
                    ['mentorIdx' => 1, 'in' => '10:00', 'out' => '11:29'],
                    ['mentorIdx' => 2, 'in' => '10:01', 'out' => '11:29']
                ],
                [
                    ['mentorIdx' => 0, 'time' => '10:00:00']
                ]
            ),

            // Two absent
            array(
                [
                    ['mentorIdx' => 0, 'in' => '9:59', 'out' => '11:29']
                ],
                [
                    ['mentorIdx' => 1, 'time' => '10:00:00'],
                    ['mentorIdx' => 2, 'time' => '10:00:00']
                ]
            ),

            // All absent
            array(
                [],
                [
                    ['mentorIdx' => 0, 'time' => '10:00:00'],
                    ['mentorIdx' => 1, 'time' => '10:00:00'],
                    ['mentorIdx' => 2, 'time' => '10:00:00']
                ]
            )
        );
    }

    /**
     * @dataProvider createNotifiedAbsenceData
     * @param $absenceTime
     * @param $expectedPenalty
     */
    public function testNotifiedAbsencePenalties($absenceTime, $expectedPenalty) {
        $date = '2018-10-12';
        $testMentor = $this->mentors[0];

        $scheduledShift = $this->createShift('10am', '11:30am', $date, [$testMentor]);

        $assignments = $scheduledShift->getAssignments();
        $mentorAssignment = null;
        foreach ($assignments as $assignment) {
            if ($assignment->getMentor() == $testMentor)
                $mentorAssignment = $assignment;
        }

        $this->createAbsenceForShiftAssignmentAtTime($mentorAssignment, $absenceTime);

        $this->entityManager->flush();

        // Must clear because we updated the database directly, otherwise the timesheets and absences will not be reflected
        $this->entityManager->clear();

        $this->executeDetectAbsences($date);

        /** @var AbsenceOccurrence[] $absenceOccurrences */
        $absenceOccurrences = $this->entityManager
            ->getRepository(AbsenceOccurrence::class)
            ->findAll();

        self::assertEquals(1, count($absenceOccurrences));
        $absenceOccurrence = $absenceOccurrences[0];
        self::assertEquals($expectedPenalty, $absenceOccurrence->getPoints());
        self::assertEquals($testMentor->getId(), $absenceOccurrence->getSubject()->getId());
    }

    public function createNotifiedAbsenceData() {
        return array(
            // Really early notice
            array('2018-09-01 12:34', -3),

            // Early notice
            array('2018-10-10 9:59', -3),

            // Boundary value notice
            array('2018-10-11 10:00', -3),

            // Late notice
            array('2018-10-11 15:00', -4),

            // Really late notice
            array('2018-10-12 9:59', -4),

            // Notice after the fact (shouldn't be possible, but just in case)
            array('2018-10-12 10:01', -5),

            // Notice way after the fact (again, shouldn't be possible, but just in case)
            array('2019-12-31 10:01', -5),
        );
    }

    /**
     * Check if absence detection still works for a mentor who should be covering a shift
     *
     * @dataProvider createCoverAbsenceData
     * @param $subTimeIn
     * @param $subTimeOut
     * @param $expectedAbsence
     */
    public function testCoverAbsence($subTimeIn, $subTimeOut, $expectedAbsence) {
        $date = '2018-10-12';
        $testMentor = $this->mentors[0];
        $substituteMentor = $this->mentors[1];

        $scheduledShift = $this->createShift('10am', '11:30am', $date, [$testMentor]);

        $mentorAssignment = $this->getShiftAssignment($scheduledShift, $testMentor);

        $absence = $this->createAbsenceForShiftAssignmentAtTime($mentorAssignment, '2018-09-01 12:34');

        $substituteShift = new ShiftAssignment($scheduledShift, $mentorAssignment->getSubject(), $substituteMentor);
        $absence->setSubstitute($substituteShift);

        $this->entityManager->persist($absence);
        $this->entityManager->persist($substituteShift);

        if ($subTimeIn && $subTimeOut) {
            $dateTimeIn = $date . ' ' . $subTimeIn;
            $dateTimeOut = $date . ' ' . $subTimeOut;
            $this->createTimesheetForTimes(
                $substituteMentor,
                new \DateTime($dateTimeIn),
                new \DateTime($dateTimeOut)
            );
        }  else if ($subTimeIn) {
            $dateTimeIn = $date . ' ' . $subTimeIn;
            $this->createTimesheetForTimes(
                $substituteMentor,
                new \DateTime($dateTimeIn),
                null
            );
        }

        $this->entityManager->flush();

        // Must clear because we updated the database directly, otherwise the timesheets and absences will not be reflected
        $this->entityManager->clear();

        $this->executeDetectAbsences($date);

        /** @var AbsenceOccurrence[] $absenceOccurrences */
        $absenceOccurrences = $this->entityManager
            ->getRepository(AbsenceOccurrence::class)
            ->findAll();

        // Should have original absence as well as substitute absence if they were absent
        self::assertEquals(count($absenceOccurrences), $expectedAbsence ? 2 : 1);

        // Make sure one of the absences is the original mentor
        $originalMentorAbsenceOccurrences = $this->entityManager->getRepository(AbsenceOccurrence::class)
            ->findBySubject($testMentor);
        self::assertEquals(1, count($originalMentorAbsenceOccurrences));
        $originalMentorAbsenceOccurrence = $originalMentorAbsenceOccurrences[0];
        self::assertEquals($testMentor->getId(), $originalMentorAbsenceOccurrence->getSubject()->getId());
        self::assertEquals(-3, $originalMentorAbsenceOccurrence->getPoints());
        self::assertEquals(new \DateTime($date . ' 10:00:00'), $originalMentorAbsenceOccurrence->getShiftDateTime());

        $substituteMentorAbsenceOccurrences = $this->entityManager->getRepository(AbsenceOccurrence::class)
            ->findBySubject($substituteMentor);
        if ($expectedAbsence) {
            self::assertEquals(1, count($substituteMentorAbsenceOccurrences));
            $substituteMentorAbsenceOccurrence = $substituteMentorAbsenceOccurrences[0];
            self::assertEquals($substituteMentor->getId(), $substituteMentorAbsenceOccurrence->getSubject()->getId());
            self::assertEquals(-5, $substituteMentorAbsenceOccurrence->getPoints());
            self::assertEquals(new \DateTime($date . ' 10:00:00'), $substituteMentorAbsenceOccurrence->getShiftDateTime());
        } else {
            self::assertEquals(count($substituteMentorAbsenceOccurrences), 0);
        }

        // Make sure shift covered and claim shift get created
        $shiftCoveredOccurrences = $this->entityManager->getRepository(ShiftCoveredOccurrence::class)->findAll();
        self::assertEquals(1, count($shiftCoveredOccurrences));
        $shiftCoveredOccurrence = $shiftCoveredOccurrences[0];
        self::assertEquals($testMentor->getId(), $shiftCoveredOccurrence->getSubject()->getId());
        self::assertEquals($substituteMentor->getId(), $shiftCoveredOccurrence->getCoveredBy()->getId());
        self::assertEquals(1, $shiftCoveredOccurrence->getPoints());

        $claimShiftOccurrences = $this->entityManager->getRepository(ClaimShiftOccurrence::class)->findAll();
        self::assertEquals(1, count($claimShiftOccurrences));
        $claimShiftOccurrence = $claimShiftOccurrences[0];
        self::assertEquals($substituteMentor->getId(), $claimShiftOccurrence->getSubject()->getId());
        self::assertEquals($testMentor->getId(), $claimShiftOccurrence->getCoveringFor()->getId());
        self::assertEquals(2, $claimShiftOccurrence->getPoints());
    }

    public function createCoverAbsenceData() {
        return array(
            // ---- Present ----

            // Signed in early
            array('9:59', '11:31', false),

            // Signed in right on time
            array('10:00', '11:31', false),

            // Signed in during shift
            array('10:08', '11:25', false),

            // Signed in early and left early
            array('9:59', '11:00', false),

            // No sign-out
            array('9:59', '', false),


            // ---- Absent ----

            // Signed in early but signed out before shift even started
            array('8:00', '9:59', true),

            // Signed in once shift ends
            array('11:30', '12:55', true),

            // Signed in after shift ended
            array('11:31', '12:55', true),

            // No sign-in
            array('', '', true),

            // No sign-out
            array('11:30', '', true),
        );
    }


    // -----------------------------------------------------------------------
    // Helper for combining data from the related entities
    // -----------------------------------------------------------------------

    /**
     * @param User $mentor
     * @param ScheduledShift $scheduledShift
     * @return ShiftAssignment|null
     */
    protected function getShiftAssignment(ScheduledShift $scheduledShift, User $mentor) {
        $assignments = $scheduledShift->getAssignments();
        $mentorAssignment = null;
        foreach ($assignments as $assignment) {
            if ($assignment->getMentor() == $mentor)
                return $assignment;
        }
        return null;
    }


    // -----------------------------------------------------------------------
    // Helpers for creating schedule entities for test cases
    // -----------------------------------------------------------------------

    /**
     * Creates and returns a scheduled shift
     *
     * @param $startTime
     * @param $endTime
     * @param $date
     * @param $shiftLeader
     * @param $mentors
     * @return ScheduledShift
     */
    protected function createShift($startTime, $endTime, $date, $mentors, $shiftLeader = null) {
        $dateAsObject = new \DateTime($date);
        $day = $dateAsObject->format('w');

        $shift = new Shift($this->schedule, $this->room, new \DateTime($startTime), new \DateTime($endTime), $day);
        $shift->addSubject($this->subject, count($mentors));

        if ($shiftLeader) {
            $shift->assignShiftLeader($shiftLeader);
        }

        foreach ($mentors as $mentor) {
            $shift->addMentor($this->subject, $mentor);
        }

        $this->entityManager->persist($shift);

        $scheduledShift = new ScheduledShift($this->schedule, $shift, new \DateTime($date));
        $this->entityManager->persist($scheduledShift);

        return $scheduledShift;
    }

    /**
     * Timesheet values are not (and should not be) settable, so to create Timesheets for testing,
     * the values in the database must be updated manually.
     *
     * @param $mentor
     * @param \DateTime $start
     * @param \DateTime $end | null
     */
    protected function createTimesheetForTimes($mentor, \DateTime $start, \DateTime $end = null) {
        // Create timesheet record and write it to database
        $timesheet = new Timesheet($mentor);
        $this->entityManager->persist($timesheet);
        $this->entityManager->flush();

        // Manually update start and end times
        $this->entityManager->getRepository(Timesheet::class)
            ->createQueryBuilder('t')
            ->update('App:Schedule\Timesheet', 't')
            ->set('t.timeIn', ':time_in')
            ->set('t.timeOut', ':time_out')
            ->where('t.id = :tid')
            ->setParameters(array(
                'time_in' => new \DateTime($start->format('Y-m-d H:i:s')),
                'time_out' => $end ? new \DateTime($end->format('Y-m-d H:i:s')) : null,
                'tid' => $timesheet->getId()
            ))
            ->getQuery()
            ->execute();
        $this->entityManager->flush();
    }

    /**
     * Absence creation times are not (and should not be) settable, so to create Absences for testing,
     * the values in the database must be updated manually.
     *
     * @param $shiftAssignment
     * @param $absenceCreationTime
     * @return Absence
     */
    protected function createAbsenceForShiftAssignmentAtTime(ShiftAssignment $shiftAssignment, $absenceCreationTime) {
        // Create absence record and write it to database
        $absence = new Absence();
        $absence->setReason("Testing");
        $shiftAssignment->setAbsence($absence);
        $this->entityManager->persist($absence);
        $this->entityManager->persist($shiftAssignment);
        $this->entityManager->flush();

        // Manually update created time
        $this->entityManager->getRepository(Absence::class)
            ->createQueryBuilder('a')
            ->update('App:Schedule\Absence', 'a')
            ->set('a.createdOn', ':created_on')
            ->where('a.id = :aid')
            ->setParameters(array(
                'created_on' => new \DateTime($absenceCreationTime),
                'aid' => $absence->getId()
            ))
            ->getQuery()
            ->execute();
        $this->entityManager->flush();

        return $absence;
    }



    // -----------------------------------------------------------------------
    // Common data for all test cases
    // -----------------------------------------------------------------------

    protected function createTestData()
    {
        $this->createTestMentors();
        $this->createTestSemester();
        $this->createTestPenalties();
        $this->entityManager->flush();
    }

    /**
     * Creates 3 mentors to use in test cases
     */
    protected function createTestMentors() {
        $mentorRole = new Role('mentor');
        $this->entityManager->persist($mentorRole);

        for ($i = 0; $i < 3; $i++) {
            $mentor = new User('First_' . $i, 'Last_' . $i, 'mxm00000' . $i);
            $mentor->addRole($mentorRole);
            $this->entityManager->persist($mentor);
            $this->mentors[] = $mentor;
        }
    }

    /**
     * Creates a test semester lasting the month of October 2018
     */
    protected function createTestSemester() {
        $this->semester = new Semester(Semester::SEASON_DEV, 2018,
            new \DateTime('2018-10-01'), new \DateTime('2018-10-31'), true);

        $this->entityManager->persist($this->semester);

        $this->schedule = new Schedule($this->semester);
        $this->entityManager->persist($this->schedule);

        $this->room = new Room('Building', 1, 1, 'Desc', 40, true);
        $this->entityManager->persist($this->room);

        $this->subject = new Subject('Test Subject', 'TS');
        $this->entityManager->persist($this->subject);
    }

    /**
     * Creates absence penalties for the initially proposed default values:
     *      No notice:          -5 points
     *      < 24 hours notice:  -4 points
     *      > 24 hours notice:  -3 points
     */
    protected function createTestPenalties() {
        $penaltyManager = AttendancePenaltyPersistenceManager::loadModel($this->entityManager);
        $penaltyManager->createAbsenceWithoutNoticePenalty(-5);

        // Justified Absences penalties will never be automatically assigned,
        // so only Unjustified Absence penalties are included in this test set
        $penaltyManager->createUnjustifiedAbsenceWithNoticePenalties(array(-4, -3), array(24));

        $penaltyManager->createShiftCoveredBonus(1);
        $penaltyManager->createClaimShiftBonus(2);

        $penaltyManager->persistModel($this->entityManager);
    }

}