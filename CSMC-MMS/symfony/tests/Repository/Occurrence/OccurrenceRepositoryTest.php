<?php

namespace App\Tests\Repository\Occurrence;

use App\Entity\Occurrence\CumulativeTardinessOccurrence;
use App\Entity\Occurrence\Occurrence;
use App\Entity\Occurrence\TardinessOccurrence;
use App\Entity\User\User;
use App\Tests\Base\PersistenceTest;

class OccurrenceRepositoryTest extends PersistenceTest
{
    /**
     * @var Occurrence[]
     */
    private $closedOccurrencesToDisplay;

    /**
     * @var Occurrence[]
     */
    private $pendingOccurrencesToDisplay;

    public function testFindClosedOccurrencesForDisplaying()
    {
        $occurrences = $this->entityManager->getRepository(Occurrence::class)->findClosedOccurrencesForDisplaying();
        $this->assertEqualsByIDs($this->closedOccurrencesToDisplay, $occurrences,
            'Displayed occurrences must be closed and not contain any 0 point tardies');
    }

    public function testFindPendingOccurrencesForDisplaying()
    {
        $actual = $this->entityManager->getRepository(Occurrence::class)->findPendingOccurrencesForDisplaying();
        $this->assertEqualsByIDs($this->pendingOccurrencesToDisplay, $actual,
            'Displayed occurrences must be pending and not contain any 0 point tardies');
    }

    /**
     * @inheritdoc
     * @throws \Doctrine\ORM\ORMException
     */
    protected function createTestData()
    {
        $mentor = new User('Test', 'Mentor', 'mxm000000');
        $this->entityManager->persist($mentor);

        // Create some tardiness occurrences, all with nonzero points
        for ($i = 0; $i < 10; $i++) {
            // Pending
            $pendingOccurrence = new TardinessOccurrence($mentor, -1, 5);
            $this->entityManager->persist($pendingOccurrence);
            $this->pendingOccurrencesToDisplay[] = $pendingOccurrence;

            // Closed
            $closedOccurrence = new TardinessOccurrence($mentor, -1, 5);
            $closedOccurrence->approve();
            $this->closedOccurrencesToDisplay[] = $closedOccurrence;
            $this->entityManager->persist($closedOccurrence);

            // Cumulative closed
            $accumulatedOccurrences = array();
            // Accumulated occurrences
            for ($j = 0; $j < 5; $j++) {
                // Accumulated ones have zero points
                $newAccumulated = new TardinessOccurrence($mentor, 0, 5);
                $accumulatedOccurrences[] = $newAccumulated;
                $this->entityManager->persist($newAccumulated);
            }
            $cumulativeOccurrence = new CumulativeTardinessOccurrence($mentor, -1, 5,
                new \DateTime('yesterday'), new \DateTime(), $accumulatedOccurrences);
            $cumulativeOccurrence->approve();
            $this->closedOccurrencesToDisplay[] = $cumulativeOccurrence;
            $this->entityManager->persist($cumulativeOccurrence);
        }

    }
}
