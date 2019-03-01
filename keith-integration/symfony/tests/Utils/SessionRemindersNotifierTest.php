<?php

namespace App\Tests\Utils;

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
use App\Entity\User\Info\NotificationPreferences;
use App\Entity\User\Role;
use App\Entity\User\User;
use App\Tests\Base\PersistenceTest;
use App\Tests\TestUtils\MockSessionRemindersNotifier;
use App\Tests\TestUtils\ReflectionUtils;

class SessionRemindersNotifierTest extends PersistenceTest
{
    /**
     * Tests that one mentor receives the correct notifications (or none if they are disabled) given the session
     * assignments.
     *
     * @dataProvider createTestSendNotificationsData
     * @param bool $receiveNotifications
     * @param int $daysBefore
     * @param array $sessionAssignments `[daysOut => [ [startTime, endTime], ... ], ... ]`
     *  daysOut: amount of days until the session, can be 0 for today
     *  startTime and endTime: should be valid for the DateTime constructor
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function testNotifyOneMentor(bool $receiveNotifications, int $daysBefore, array $sessionAssignments = null)
    {

        $mentorRole = new Role('mentor');
        $this->entityManager->persist($mentorRole);
        $mentor = new User('Mentor', 'One', 'mxm00000');
        $mentor->addRole($mentorRole);
        $this->initializeNotificationPreferences($mentor, $receiveNotifications, $daysBefore);
        $this->entityManager->persist($mentor);

        $this->persistSessionAssignments($mentor, $sessionAssignments);


        $container = self::$kernel->getContainer();
        // Instantiating directly instead of getting from container so that we get a new instance every time and the
        // results stored in sentEmailInfo don't pollute the next test
        // We are using the mock instead of the real one so that no actual emails get sent and additionally we can
        // easily check that the assignments for the notification are correct
        $notifier = new MockSessionRemindersNotifier($container->get('test.email_service'),
            $container->get('twig'), $container->get('doctrine.orm.default_entity_manager'));
        $notifier->sendNotifications();

        // Expected assignments are those that are $daysBefore days from now, if any
        $expectedAssignments = null;
        if (isset($sessionAssignments[$daysBefore])) {
            $expectedAssignments = $sessionAssignments[$daysBefore];
        }

        if (!$receiveNotifications || $expectedAssignments == null) {
            self::assertEmpty($notifier->getSentEmailInfo());
        } else {
            $this->assertAssignmentsMatch($daysBefore, $expectedAssignments, $notifier->getSentEmailInfo()[0][1]);
        }
    }

    public function createTestSendNotificationsData()
    {
        return array(
            // Notifications disabled, nothing should be sent even with assignments on the day
            [false, 0, [0 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']]]],
            [false, 1, [1 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']]]],
            [false, 5, [5 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']]]],
            [false, 10, [10 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']]]],
            // Notifications disabled and there are assignments on other days
            [false, 0, [1 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']]]],
            [false, 10, [5 => [['10am', '11am']], 6 => [['1pm', '2pm'], ['4pm', '5pm']]]],
            // Notifications enabled but there are no assignments on the target day
            [true, 0, [
                1 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']],
                5 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']],
                10 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']]
            ]],
            [true, 5, [
                4 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']],
                6 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']],
                15 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']]
            ]],
            [true, 10, [
                1 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']],
                9 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']],
                11 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']],
                15 => [['10am', '11am'], ['1pm', '2pm'], ['4pm', '5pm']]
            ]],
            // Notifications are enabled and there are assignments on the target day
            [true, 0, [0 => [['3am', '5am']]]],
            [true, 0, [0 => [['7am', '9am'], ['2pm', '4pm']]]],
            [true, 0, [
                0 => [['9am', '11am'], ['5pm', '6pm']],
                1 => [['10am', '12pm'], ['3pm', '5pm']]
            ]],
            [true, 1, [
                0 => [['9am', '11am'], ['5pm', '6pm']],
                1 => [['10am', '12pm'], ['3pm', '5pm']]
            ]],
            [true, 5, [
                0 => [['9am', '11am'], ['5pm', '6pm']],
                1 => [['10am', '12pm'], ['3pm', '5pm']],
                5 => [['3am', '4am'], ['3pm', '5pm'], ['7pm','9pm']],
                9 => [['10am', '12pm'], ['3pm', '9pm']]
            ]],
        );
    }

    private function initializeNotificationPreferences(User $mentor, bool $receiveNotifications, int $daysBefore)
    {
        /** @var NotificationPreferences $preferences */
        $preferences = ReflectionUtils::extractPrivatePropertyValue($mentor, 'notificationPreferences');

        ReflectionUtils::assignValueToPrivateProperty($preferences, 'notifyBeforeSession', $receiveNotifications);
        ReflectionUtils::assignValueToPrivateProperty($preferences, 'sessionReminderAdvanceDays', $daysBefore);
        ReflectionUtils::assignValueToPrivateProperty($preferences, 'useEmail', true);
    }

    private function assertAssignmentsMatch(int $daysBefore, array $expectedAssignments, array $actualAssignments)
    {
        $expectedAssignmentsNormalized = array();
        foreach ($expectedAssignments as $tuple) {
            list($start, $end) = $tuple;
            $expectedAssignmentsNormalized[] =
                [(new \DateTime($start))->format('H:i'), (new \DateTime($end))->format('H:i')];
        }

        // Must check the returned assignments correspond to the target date
        $targetDate = new \DateTime("+$daysBefore days");
        $actualAssignmentsNormalized = array();
        /** @var ShiftAssignment $actualAssignment */
        foreach ($actualAssignments as $actualAssignment) {
            self::assertEquals($targetDate->format('Y-m-d'),
                $actualAssignment->getScheduledShift()->getDate()->format('Y-m-d'));
            $shift = $actualAssignment->getScheduledShift()->getShift();
            $actualAssignmentsNormalized[] =
                [$shift->getStartTime()->format('H:i'), $shift->getEndTime()->format('H:i')];
        }

        self::assertEquals($expectedAssignmentsNormalized, $actualAssignmentsNormalized,
            'Assignments must correspond to the same shifts', 0, 1, true);
    }

    /**
     * @param User $mentor
     * @param array|null $sessionAssignments
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Exception
     */
    private function persistSessionAssignments(User $mentor, array $sessionAssignments = null)
    {
        if ($sessionAssignments == null) {
            return;
        }

        // Boilerplate objects required to create a session assignment
        $semester = new Semester(Semester::SEASON_DEV, 2018, new \DateTime('-5 days'), new \DateTime('+100 days'), true);
        $this->entityManager->persist($semester);

        $schedule = new Schedule($semester);
        $this->entityManager->persist($schedule);

        $subject = new Subject('sub', 'sub');
        $this->entityManager->persist($subject);

        $room = new Room('B', 1, 1, 'desc', 20, true);
        $this->entityManager->persist($room);

        $sessionType = new SessionType('type', '#000000');
        $this->entityManager->persist($sessionType);

        $session = new ScheduledSession($sessionType, 'topic', 2, 'desc', 'none', 'none');
        $session->setDefaults($room, 20, new \DateInterval('PT1H'));

        // Create the actual session assignments
        foreach ($sessionAssignments as $daysOut => $sessionTuples) {
            foreach ($sessionTuples as $sessionTuple) {
                list($startTimeString, $endTimeString) = $sessionTuple;
                $shift = new Shift($schedule, $room, new \DateTime($startTimeString), new \DateTime($endTimeString), 0);
                $shift->addSubject($subject, 5);
                $shift->addMentor($subject, $mentor);
                $this->entityManager->persist($shift);

                $scheduledShift = new ScheduledShift($schedule, $shift, new \DateTime("+$daysOut days"));
                $this->entityManager->persist($scheduledShift);

                $sessionAssignment = $scheduledShift->getAssignments()[0];
                $timeSlot = new SessionTimeSlot($session, $room, new \DateTime($startTimeString), new \DateTime($endTimeString), 20);
                $timeSlot->assign($sessionAssignment);
            }
        }

        $this->entityManager->persist($session);

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    // TODO: test with multiple mentors, make sure only the right mentors get notified
    // TODO: test notification HTML content

    /**
     * @inheritdoc
     */
    protected function createTestData()
    {
    }
}
