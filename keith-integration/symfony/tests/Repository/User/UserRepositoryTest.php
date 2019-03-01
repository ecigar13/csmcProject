<?php

namespace App\Tests\Repository\User;

use App\Entity\Occurrence\BehaviorOccurrence;
use App\Entity\Occurrence\TardinessOccurrence;
use App\Entity\User\Role;
use App\Entity\User\User;
use App\Tests\Base\PersistenceTest;
use App\Tests\TestUtils\ReflectionUtils;

class UserRepositoryTest extends PersistenceTest
{

    /**
     * @var User[]
     */
    private $mentorsWithOccurrence;

    /**
     * @var User[]
     */
    private $mentorsWithoutOccurrence;

    /**
     * @var User[]
     */
    private $mentorsWithSessionReminders;

    /**
     * @var User[]
     */
    private $mentorsWithoutSessionReminders;

    /**
     * @var User[]
     */
    private $mentorsWithAssignmentNotifications;

    public function testFindMentorIdsWithPendingOccurrences()
    {
        $expected = array_map(function ($u) {
            /** @var User $u */
            return $u->getId();
        }, $this->mentorsWithOccurrence);

        self::assertEquals($expected, $this->entityManager->getRepository(User::class)->findMentorIdsWithPendingOccurrences(),
            'Only IDs of mentors that have pending occurrences should be returned', 0, 1, true);
    }

    public function testFindMentorsWithEnabledSessionReminders()
    {
        self::assertEquals($this->mentorsWithSessionReminders, $this->entityManager->getRepository(User::class)->findMentorsWithEnabledSessionReminders(),
            'Only mentors who have enabled session reminders should be returned', 0, 1, true);
    }

    public function testFindMentorsWithEnabledSessionAssignmentNotifications()
    {
        self::assertEquals($this->mentorsWithAssignmentNotifications,
            $this->entityManager->getRepository(User::class)->findMentorsWithEnabledSessionAssignmentNotifications(),
            'Only mentors with enabled assignment notifications should be returned',
            0, 1, true);
    }

    /**
     * @inheritdoc
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createTestData()
    {
        $mentorRole = new Role('mentor');
        $this->entityManager->persist($mentorRole);

        $this->mentorsWithOccurrence = array();

        for ($i = 0; $i < 10; $i++) {
            $user = new User("First $i", "Last $i", 'mxm' . str_pad($i, 6, '0', STR_PAD_LEFT));
            $user->addRole($mentorRole);
            $this->entityManager->persist($user);

            if ($i % 2 == 0) {
                $occurrence = new BehaviorOccurrence($user, 'something', 'detail', new \DateTime());
            } else {
                $occurrence = new TardinessOccurrence($user, -1.0, 5);
            }

            $this->entityManager->persist($occurrence);
            $this->mentorsWithOccurrence[] = $user;

            // Make them have no session reminders
            $this->mentorsWithoutSessionReminders[] = $user;

            // Add assignment reminders
            $preferences = ReflectionUtils::extractPrivatePropertyValue($user, 'notificationPreferences');
            ReflectionUtils::assignValueToPrivateProperty($preferences, 'notifyWhenAssigned', true);
            $this->mentorsWithAssignmentNotifications[] = $user;
        }

        $this->mentorsWithoutOccurrence = array();

        for (; $i < 20; $i++) {
            $user = new User("First $i", "Last $i", 'mxm' . str_pad($i, 6, '0', STR_PAD_LEFT));
            $user->addRole($mentorRole);
            $this->entityManager->persist($user);

            $this->mentorsWithoutOccurrence[] = $user;

            // Add session reminders
            $preferences = ReflectionUtils::extractPrivatePropertyValue($user, 'notificationPreferences');
            ReflectionUtils::assignValueToPrivateProperty($preferences, 'notifyBeforeSession', true);
            $this->mentorsWithSessionReminders[] = $user;
        }
    }
}
