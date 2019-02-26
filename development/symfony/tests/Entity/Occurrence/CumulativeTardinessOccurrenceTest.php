<?php

namespace App\Tests\Entity\Occurrence;

use App\Command\AccumulateTardinessCommand;
use App\Entity\Occurrence\CumulativeTardinessOccurrence;
use App\Entity\Occurrence\TardinessOccurrence;
use App\Entity\User\Role;
use App\Entity\User\User;
use App\Tests\Base\PersistenceTest;
use App\Utils\AttendancePenaltyPersistenceManager;
use Deployer\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class CumulativeTardinessOccurrenceTest extends PersistenceTest
{
    /**
     * @var AttendancePenaltyPersistenceManager
     */
    private $penaltyManager;

    /**
     * @var User[]
     */
    private $mentors;

    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * @param string|null $date
     */
    protected function executeAccumulateTardiness($date = null) {
        // Initialize instance of the command under test
        $application = new Application();
        $application->add(new AccumulateTardinessCommand($this->entityManager));
        $command = $application->find("app:accumulate-tardiness");
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
     * If no settings are configured, command should output nothing
     */
    function testNoPenaltySettings() {
        $this->createTardinessOccurrenceForTime($this->mentors[0], '2018-10-25', 4);
        $this->entityManager->flush();

        // Must clear because we updated the database directly
        $this->entityManager->clear();

        $this->executeAccumulateTardiness('2018-10-25');

        $cumulativeTardinessOccurrences = $this->entityManager
            ->getRepository(CumulativeTardinessOccurrence::class)->findAll();
        self::assertEquals(0, count($cumulativeTardinessOccurrences));
    }

    /**
     * If first tardiness interval is not set to cumulative, command should output nothing
     */
    function testNoCumulativeSetting() {
        $this->penaltyManager = AttendancePenaltyPersistenceManager::loadModel($this->entityManager);
        $this->penaltyManager->createTardinessPenalties([0, -1], [5, 30], false);
        $this->penaltyManager->persistModel($this->entityManager);
        $this->createTardinessOccurrenceForTime($this->mentors[0], '2018-10-25', 4);
        $this->entityManager->flush();

        // Must clear because we updated the database directly
        $this->entityManager->clear();

        $this->executeAccumulateTardiness('2018-10-25');

        $cumulativeTardinessOccurrences = $this->entityManager
            ->getRepository(CumulativeTardinessOccurrence::class)->findAll();
        self::assertEquals(0, count($cumulativeTardinessOccurrences));
    }

    /**
     * Test if the correct tardiness occurrences are being included in the accumulation, i.e.
     * tardiness occurrences that:
     *     - occurred during the correct week
     *     - have tardiness amount within the 'cumulative' threshold
     *
     * @dataProvider createTardyData
     * @param $tardies
     * @param $expectedNumAccumulated
     * @param $expectedTardinessMinutes
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    function testAccumulateTardiness($tardies, $expectedNumAccumulated, $expectedTardinessMinutes) {
        $this->penaltyManager = AttendancePenaltyPersistenceManager::loadModel($this->entityManager);
        $this->penaltyManager->createTardinessPenalties([0, -1], [5, 30], true);
        $this->penaltyManager->persistModel($this->entityManager);

        foreach ($tardies as $tardy) {
            $this->createTardinessOccurrenceForTime($this->mentors[0], $tardy['date'], $tardy['minutes']);
        }
        $this->entityManager->flush();

        // Must clear because we updated the database directly
        $this->entityManager->clear();

        $this->executeAccumulateTardiness('2018-10-25');

        /** @var CumulativeTardinessOccurrence[] $cumulativeTardinessOccurrences */
        $cumulativeTardinessOccurrences = $this->entityManager
            ->getRepository(CumulativeTardinessOccurrence::class)->findAll();
        if ($expectedTardinessMinutes > 0) {
            self::assertEquals(1, count($cumulativeTardinessOccurrences));
            $cumulativeTardinessOccurrence = $cumulativeTardinessOccurrences[0];
            self::assertEquals($expectedNumAccumulated, count($cumulativeTardinessOccurrence->getAccumulatedOccurrences()));
            self::assertEquals($expectedTardinessMinutes, $cumulativeTardinessOccurrence->getTardinessMinutes());

            $accumulatedOccurrences = $cumulativeTardinessOccurrence->getAccumulatedOccurrences();
            foreach($accumulatedOccurrences as $ac) {
                self::assertEquals($cumulativeTardinessOccurrence->getId(), $ac->getCumulativeOccurrence()->getId());
            }

            self::assertEquals($this->mentors[0]->getId(), $cumulativeTardinessOccurrence->getSubject()->getId());
        } else {
            self::assertEquals(0, count($cumulativeTardinessOccurrences));
        }
    }

    function createTardyData() {
        return array(

            // No tardiness
            array([], 0, 0),

            // One tardiness
            array([ ['date' => '2018-10-21', 'minutes' => 3] ], 1, 3),

            // Some tardies are more than threshold
            array(
                [
                    ['date' => '2018-10-25', 'minutes' => 4],
                    ['date' => '2018-10-26', 'minutes' => 3],
                    ['date' => '2018-10-26', 'minutes' => 2],
                    ['date' => '2018-10-26', 'minutes' => 1],
                    ['date' => '2018-10-26', 'minutes' => 5], // more than threshold
                    ['date' => '2018-10-26', 'minutes' => 8], // more than threshold
                    ['date' => '2018-10-27', 'minutes' => 11] // more than threshold
                ],
                4,
                10
            ),

            // Some tardiness are from a different week
            array(
                [
                    ['date' => '2018-10-20', 'minutes' => 4], // previous week
                    ['date' => '2018-10-26', 'minutes' => 3],
                    ['date' => '2018-10-26', 'minutes' => 2],
                    ['date' => '2018-10-26', 'minutes' => 1],
                    ['date' => '2018-10-28', 'minutes' => 1], // next week
                ],
                3,
                6
            ),

            // One for each day of the week
            array(
                [
                    ['date' => '2018-10-21', 'minutes' => 4],
                    ['date' => '2018-10-22', 'minutes' => 3],
                    ['date' => '2018-10-23', 'minutes' => 2],
                    ['date' => '2018-10-24', 'minutes' => 1],
                    ['date' => '2018-10-25', 'minutes' => 4],
                    ['date' => '2018-10-26', 'minutes' => 3],
                    ['date' => '2018-10-27', 'minutes' => 2],
                ],
                7,
                19
            )
        );
    }

    /**
     * @dataProvider createMultipleMentorTardyData
     * @param $tardies
     * @param $expectedNumAccumulated
     * @param $expectedTardinessMinutes
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    function testAccumulateTardinessForMultipleMentors($tardies, $expectedNumAccumulated, $expectedTardinessMinutes) {
        $this->penaltyManager = AttendancePenaltyPersistenceManager::loadModel($this->entityManager);
        $this->penaltyManager->createTardinessPenalties([0, -1], [5, 30], true);
        $this->penaltyManager->persistModel($this->entityManager);

        foreach ($tardies as $tardy) {
            $this->createTardinessOccurrenceForTime($this->mentors[$tardy['mentorIdx']], $tardy['date'], $tardy['minutes']);
        }
        $this->entityManager->flush();

        // Must clear because we updated the database directly
        $this->entityManager->clear();

        $this->executeAccumulateTardiness('2018-10-25');

        $expectedTotalNum = array_reduce($expectedNumAccumulated, function($carry, $item) {
            $carry += $item ? 1 : 0;
            return $carry;
        });

        $cumulativeTardinessOccurrences = $this->entityManager
            ->getRepository(CumulativeTardinessOccurrence::class)
            ->findAll();

        self::assertEquals($expectedTotalNum, count($cumulativeTardinessOccurrences));

        foreach ($this->mentors as $idx => $mentor) {
            /** @var CumulativeTardinessOccurrence[] $cumulativeTardinessOccurrences */
            $cumulativeTardinessOccurrences = $this->entityManager
                ->getRepository(CumulativeTardinessOccurrence::class)
                ->findBySubject($mentor);
            if ($expectedTardinessMinutes[$idx] > 0) {
                self::assertEquals(1, count($cumulativeTardinessOccurrences));
                $cumulativeTardinessOccurrence = $cumulativeTardinessOccurrences[0];
                self::assertEquals($expectedNumAccumulated[$idx], count($cumulativeTardinessOccurrence->getAccumulatedOccurrences()));
                self::assertEquals($expectedTardinessMinutes[$idx], $cumulativeTardinessOccurrence->getTardinessMinutes());

                $accumulatedOccurrences = $cumulativeTardinessOccurrence->getAccumulatedOccurrences();
                foreach($accumulatedOccurrences as $ac) {
                    self::assertEquals($cumulativeTardinessOccurrence->getId(), $ac->getCumulativeOccurrence()->getId());
                }

                self::assertEquals($this->mentors[$idx]->getId(), $cumulativeTardinessOccurrence->getSubject()->getId());
            } else {
                self::assertEquals(0, count($cumulativeTardinessOccurrences));
            }
        }
    }

    function createMultipleMentorTardyData() {
        return array(

            // No tardiness
            array([], [0, 0, 0], [0, 0, 0]),

            // One tardiness
            array(
                [
                    ['mentorIdx' => 2, 'date' => '2018-10-21', 'minutes' => 3]
                ],
                [0, 0, 1],
                [0, 0, 3]
            ),

            // Some tardies are more than threshold
            array(
                [
                    ['mentorIdx' => 0, 'date' => '2018-10-25', 'minutes' => 4],
                    ['mentorIdx' => 1, 'date' => '2018-10-26', 'minutes' => 3],
                    ['mentorIdx' => 2, 'date' => '2018-10-26', 'minutes' => 2],
                    ['mentorIdx' => 0, 'date' => '2018-10-26', 'minutes' => 1],
                    ['mentorIdx' => 0, 'date' => '2018-10-26', 'minutes' => 5], // more than threshold
                    ['mentorIdx' => 1, 'date' => '2018-10-26', 'minutes' => 8], // more than threshold
                    ['mentorIdx' => 2, 'date' => '2018-10-27', 'minutes' => 11] // more than threshold
                ],
                [2, 1, 1],
                [5, 3, 2]
            ),

            // Some tardiness are from a different week
            array(
                [
                    ['mentorIdx' => 0, 'date' => '2018-10-20', 'minutes' => 4], // previous week
                    ['mentorIdx' => 1, 'date' => '2018-10-26', 'minutes' => 3],
                    ['mentorIdx' => 2, 'date' => '2018-10-26', 'minutes' => 2],
                    ['mentorIdx' => 2, 'date' => '2018-10-26', 'minutes' => 1],
                    ['mentorIdx' => 1, 'date' => '2018-10-28', 'minutes' => 1], // next week
                ],
                [0, 1, 2],
                [0, 3, 3]
            ),

            // One for each day of the week
            array(
                [
                    ['mentorIdx' => 0, 'date' => '2018-10-21', 'minutes' => 4],
                    ['mentorIdx' => 1, 'date' => '2018-10-22', 'minutes' => 3],
                    ['mentorIdx' => 2, 'date' => '2018-10-23', 'minutes' => 2],
                    ['mentorIdx' => 1, 'date' => '2018-10-24', 'minutes' => 1],
                    ['mentorIdx' => 0, 'date' => '2018-10-25', 'minutes' => 4],
                    ['mentorIdx' => 1, 'date' => '2018-10-26', 'minutes' => 3],
                    ['mentorIdx' => 2, 'date' => '2018-10-27', 'minutes' => 2],
                ],
                [2, 3, 2],
                [8, 7, 4]
            )
        );
    }

    /**
     * NOTE: The use case for this test scenario is unlikely
     *
     * Creates tardiness occurrences for the week, then executes the command to accumulate tardiness
     * for the week. Then creates more tardiness occurrences for the same week, and executes the
     * command to accumulate tardiness for the week again. In this scenario, a new cumulative tardiness
     * occurrence should be created, and the previously accumulated tardiness should not be included.
     */
    function testProtectAgainstDoubleCounting() {
        $this->penaltyManager = AttendancePenaltyPersistenceManager::loadModel($this->entityManager);
        $this->penaltyManager->createTardinessPenalties([0, -1], [5, 30], true);
        $this->penaltyManager->persistModel($this->entityManager);

        $tardiesPart1 = [
            ['date' => '2018-10-21', 'minutes' => 4],
            ['date' => '2018-10-22', 'minutes' => 3],
            ['date' => '2018-10-23', 'minutes' => 2],
            ['date' => '2018-10-24', 'minutes' => 1]
        ];

        foreach ($tardiesPart1 as $tardy) {
            $this->createTardinessOccurrenceForTime($this->mentors[0], $tardy['date'], $tardy['minutes']);
        }
        $this->entityManager->flush();

        $this->executeAccumulateTardiness('2018-10-25');

        $tardiesPart2 = [
            ['date' => '2018-10-25', 'minutes' => 4],
            ['date' => '2018-10-26', 'minutes' => 3],
            ['date' => '2018-10-27', 'minutes' => 2]
        ];

        foreach ($tardiesPart2 as $tardy) {
            $this->createTardinessOccurrenceForTime($this->mentors[0], $tardy['date'], $tardy['minutes']);
        }
        $this->entityManager->flush();

        // Must clear because we updated the database directly
        $this->entityManager->clear();

        $this->executeAccumulateTardiness('2018-10-25');

        /** @var CumulativeTardinessOccurrence[] $cumulativeTardinessOccurrences */
        $cumulativeTardinessOccurrences = $this->entityManager
            ->getRepository(CumulativeTardinessOccurrence::class)
            ->findBy([], ['creationDate' => 'ASC']);

        $originalCumulativeOccurrence = $cumulativeTardinessOccurrences[0];
        self::assertEquals(4, count($originalCumulativeOccurrence->getAccumulatedOccurrences()));
        self::assertEquals(10, $originalCumulativeOccurrence->getTardinessMinutes());

        $accumulatedOccurrences = $originalCumulativeOccurrence->getAccumulatedOccurrences();
        foreach($accumulatedOccurrences as $ac) {
            self::assertEquals($originalCumulativeOccurrence->getId(), $ac->getCumulativeOccurrence()->getId());
        }

        self::assertEquals($this->mentors[0]->getId(), $originalCumulativeOccurrence->getSubject()->getId());


        self::assertEquals(2, count($cumulativeTardinessOccurrences));
        $anotherTardinessOccurrence = $cumulativeTardinessOccurrences[1];
        self::assertEquals(3, count($anotherTardinessOccurrence->getAccumulatedOccurrences()));
        self::assertEquals(9, $anotherTardinessOccurrence->getTardinessMinutes());

        $accumulatedOccurrences = $anotherTardinessOccurrence->getAccumulatedOccurrences();
        foreach($accumulatedOccurrences as $ac) {
            self::assertEquals($anotherTardinessOccurrence->getId(), $ac->getCumulativeOccurrence()->getId());
        }

        self::assertEquals($this->mentors[0]->getId(), $anotherTardinessOccurrence->getSubject()->getId());
    }

    // -----------------------------------------------------------------------
    // Data initialization and modification functions
    // -----------------------------------------------------------------------

    function createTestData() {
        // Create test mentor
        $mentorRole = new Role('mentor');
        $this->entityManager->persist($mentorRole);

        for ($i = 0; $i < 3; $i++) {
            $mentor = new User('First_' . $i, 'Last_' . $i, 'txt00000' . $i);
            $mentor->addRole($mentorRole);
            $this->entityManager->persist($mentor);
            $this->mentors[] = $mentor;
        }

        $this->entityManager->flush();
    }

    /**
     * Tardiness occurrence creation times are not (and should not be) settable, so to create tardiness
     * occurrences for testing, the values in the database must be updated manually.
     *
     * @param User $mentor
     * @param $tardinessDate
     * @param $minutesTardy
     * @return TardinessOccurrence
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function createTardinessOccurrenceForTime(User $mentor, $tardinessDate, $minutesTardy) {
        // Create tardiness occurrence and write it to database
        $tardy = new TardinessOccurrence($mentor, 0, $minutesTardy);
        $this->entityManager->persist($tardy);
        $this->entityManager->flush();

        // Manually update created time
        $this->entityManager->getRepository(TardinessOccurrence::class)
            ->createQueryBuilder('t')
            ->update('App:Occurrence\TardinessOccurrence', 't')
            ->set('t.creationDate', ':tardiness_date')
            ->where('t.id = :tid')
            ->setParameters(array(
                'tardiness_date' => new \DateTime($tardinessDate),
                'tid' => $tardy->getId()
            ))
            ->getQuery()
            ->execute();
        $this->entityManager->flush();

        return $tardy;
    }

}