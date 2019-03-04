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
use App\Tests\TestUtils\MockSessionAssignmentsNotifier;
use App\Tests\TestUtils\ReflectionUtils;

class SessionAssignmentsNotifierTest extends PersistenceTest
{

    /**
     * @dataProvider createNotifyOneMentorData
     * @param bool $receiveNotifications
     * @param array $sessionAssignments
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function testNotifyOneMentor(bool $receiveNotifications, array $sessionAssignments = null)
    {
        $mentorRole = new Role('mentor');
        $this->entityManager->persist($mentorRole);
        $mentor = new User('Test', 'Mentor', 'mxm000000');
        $mentor->addRole($mentorRole);
        $this->initializeNotificationPreferences($mentor, $receiveNotifications);
        $this->entityManager->persist($mentor);

        $expectedAssignments = $this->persistSessionAssignments($mentor, $sessionAssignments);

        $container = self::$kernel->getContainer();
        // Instantiating directly instead of getting from container so that we get a new instance every time and the
        // results stored in sentEmailInfo don't pollute the next test
        // We are using the mock instead of the real one so that no actual emails get sent and additionally we can
        // easily check that the assignments for the notification are correct
        $notifier = new MockSessionAssignmentsNotifier($container->get('test.email_service'),
            $container->get('twig'), $container->get('doctrine.orm.default_entity_manager'));
        $notifier->sendNotifications();

        if (!$receiveNotifications || $expectedAssignments == null) {
            self::assertEmpty($notifier->getSentEmailInfo());
        } else {
            $this->assertAssignmentsMatch($expectedAssignments, $notifier->getSentEmailInfo()[0][1]);
        }
    }

    public function createNotifyOneMentorData()
    {
        return array(
            // Notifications are disabled
            [false],
            [false, [0 => [['1am', '2am']]]],
            [false, [0 => [['1am', '2am'], ['10pm', '11pm']]]],
            [false, [
                0 => [['9am', '11am'], ['2pm', '4pm']],
                5 => [['10am', '11am'], ['5pm', '7pm']]
            ]],
            // Notifications enabled but there are no session assignments today
            [true],
            [true, [1 => [['1am', '2am']]]],
            [true, [2 => [['1am', '2am'], ['10pm', '11pm']]]],
            [true, [
                5 => [['9am', '11am'], ['2pm', '4pm']],
                10 => [['10am', '11am'], ['5pm', '7pm']]
            ]],
            // Should receive notifications
            [true, [0 => [['9am', '10am']]]],
            [true, [0 => [['9am', '10am'], ['2pm', '3pm']]]],
            [true, [
                0 => [['1am', '3pm'], ['10pm', '11pm']],
                5 => [['9am', '11am'], ['2pm', '4pm']],
                10 => [['10am', '11am'], ['5pm', '7pm']]
            ]],
        );
    }

    private function initializeNotificationPreferences(User $mentor, bool $receiveNotifications)
    {
        /** @var NotificationPreferences $preferences */
        $preferences = ReflectionUtils::extractPrivatePropertyValue($mentor, 'notificationPreferences');

        ReflectionUtils::assignValueToPrivateProperty($preferences, 'notifyWhenAssigned', $receiveNotifications);
        ReflectionUtils::assignValueToPrivateProperty($preferences, 'useEmail', true);
    }

    /**
     * @param ShiftAssignment[] $expectedAssignments
     * @param ShiftAssignment[] $actualAssignments
     */
    private function assertAssignmentsMatch(array $expectedAssignments, array $actualAssignments)
    {
        $extractID = function ($sa) {
            /** @var ShiftAssignment $sa */
            return $sa->getId();
        };
        $expectedAssignmentsIDs = array_map($extractID, $expectedAssignments);

        $actualAssignmentsIDs = array_map($extractID, $actualAssignments);

        self::assertEquals($expectedAssignmentsIDs, $actualAssignmentsIDs,
            'Assignments for notifications must be the ones that happen today', 0, 1, true);
    }

    /**
     * @param User $mentor
     * @param array|null $sessionAssignments
     * @return ShiftAssignment[]|null The assignments for which a notification is expected
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Exception
     */
    private function persistSessionAssignments(User $mentor, array $sessionAssignments = null)
    {
        if ($sessionAssignments == null) {
            return null;
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

        $expectedAssignments = array();

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

                // assignedOn will be set to now, so we need to fake it
                ReflectionUtils::assignValueToPrivateProperty($sessionAssignment, 'assignedOn',
                    new \DateTime("+$daysOut days"));

                // It is expected for notification if it happens today
                if ($daysOut == 0) {
                    $expectedAssignments[] = $sessionAssignment;
                }
            }
        }

        $this->entityManager->persist($session);

        $this->entityManager->flush();
        $this->entityManager->clear();

        return $expectedAssignments;
    }

    // TODO: test that the correct mentors get the notification when there are multiple mentors
    // TODO: test notification HTML content

    /**
     * @inheritdoc
     */
    protected function createTestData()
    {
    }
}
