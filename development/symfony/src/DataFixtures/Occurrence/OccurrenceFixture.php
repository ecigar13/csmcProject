<?php


namespace App\DataFixtures\Occurrence;

use App\DataFixtures\User\UserFixture;
use App\Entity\Occurrence\AbsenceOccurrence;
use App\Entity\Occurrence\BehaviorOccurrence;
use App\Entity\Occurrence\ClaimShiftOccurrence;
use App\Entity\Occurrence\CumulativeTardinessOccurrence;
use App\Entity\Occurrence\Occurrence;
use App\Entity\Occurrence\ShiftCoveredOccurrence;
use App\Entity\Occurrence\TardinessOccurrence;
use App\Entity\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class OccurrenceFixture extends Fixture implements DependentFixtureInterface
{
    const DEFAULT_OCCURRENCES = [
        [
            "type" => "Rude to student",
            "description" => "Told the student to just Google his question.",
            "points" => -4
        ],
        [
            "type" => "Left shift early",
            "description" => "Just left, didn't even tell us why.",
            "points" => -3
        ],
        [
            "type" => "Sassiness",
            "description" => "Rolled their eyes at me when I asked them to help a student.",
            "points" => -2
        ],
        [
            "type" => "Above and beyond",
            "description" => "Turned 15 students into inductive proof masters.",
            "points" => 5
        ],
        [
            "type" => "Staying late to help",
            "description" => "Took initiative to see through a student's problem until it was solved.",
            "points" => 3
        ]
    ];

    /**
     * @inheritdoc
     */
    public function load(ObjectManager $manager)
    {
        for ($n = 0; $n < UserFixture::MENTOR_AMOUNT; $n++) {
            $p = str_pad($n, 6, '0', STR_PAD_LEFT);
            /** @var User $user */
            $user = $this->getReference(UserFixture::MENTOR . $p);
            $this->addRandomBehaviorOccurrences($user);
            $this->addRandomAbsences($user);
            $this->addRandomCumulativeTardiness($user);
            $this->addRandomTardiness($user);
            $manager->persist($user);
        }

        $this->addClaimCoverShift($manager);

        $manager->flush();
    }

    /**
     * @inheritdoc
     */
    public function getDependencies()
    {
        return array(
            UserFixture::class
        );
    }

    private function addClaimCoverShift(ObjectManager $manager) {
        /** @var User $originalMentor */
        /** @var User $substituteMentor */
        $originalMentor = $this->getReference(UserFixture::MENTOR . '000000');
        $substituteMentor = $this->getReference(UserFixture::MENTOR . '000001');

        $coverOccurrence = new ShiftCoveredOccurrence($originalMentor, new \DateTime('11:30:00'), $substituteMentor, 1);
        $claimOccurrence = new ClaimShiftOccurrence($substituteMentor, new \DateTime('11:30:00'), $originalMentor, 2);
        $this->decideRandomStatus($coverOccurrence);
        $this->decideRandomStatus($claimOccurrence);

        $manager->persist($originalMentor);
        $manager->persist($substituteMentor);
    }

    private function addRandomTardiness(User $user) {
        // 33% chance
        if (rand(0, 2) == 0) {
            $occurrence = new TardinessOccurrence($user, -1, 15);
            $this->decideRandomStatus($occurrence);
        }

        // 33% chance
        if (rand(0, 2) == 0) {
            $occurrence = new TardinessOccurrence($user, -0.5, 8);
            $this->decideRandomStatus($occurrence);
        }
    }

    /**
     * @param User $user
     */
    private function addRandomBehaviorOccurrences(User $user)
    {
        // 33% chance
        if (rand(0, 2) == 0) {
            $defaultOccurrence = OccurrenceFixture::DEFAULT_OCCURRENCES[rand(0, 4)];
            $occurrence = new BehaviorOccurrence($user, $defaultOccurrence["type"], $defaultOccurrence["description"], new \DateTime());
            $occurrence->setPoints($defaultOccurrence["points"]);
            $this->decideRandomStatus($occurrence);
        }

        // 17% chance
        if (rand(0, 5) == 0) {
            $defaultOccurrence = OccurrenceFixture::DEFAULT_OCCURRENCES[rand(0, 4)];
            $occurrence = new BehaviorOccurrence($user, $defaultOccurrence["type"], $defaultOccurrence["description"], new \DateTime());
            $occurrence->setPoints($defaultOccurrence["points"]);
            $this->decideRandomStatus($occurrence);
        }
    }

    private function addRandomAbsences(User $user)
    {
        // 20% chance
        if (rand(0, 4) == 0) {
            $randNoticeGiven = rand(0, 1) === 0;

            if ($randNoticeGiven) {
                $occurrence = new AbsenceOccurrence($user, new \DateTime('10:00:00'), AbsenceOccurrence::NO_NOTICE, -5);
            } else {
                $hoursNotice = rand(0, 36);
                $occurrence = new AbsenceOccurrence($user, new \DateTime('10:00:00'), $hoursNotice, $hoursNotice < 24 ? -4 : -3);
            }
            $this->decideRandomStatus($occurrence);
        }

        // 10% chance
        if (rand(0, 9) == 0) {
            $randNoticeGiven = rand(0, 1) === 0;

            if ($randNoticeGiven) {
                $occurrence = new AbsenceOccurrence($user, new \DateTime('10:00:00'), AbsenceOccurrence::NO_NOTICE, -5);
            } else {
                $hoursNotice = rand(0, 36);
                $occurrence = new AbsenceOccurrence($user, new \DateTime('10:00:00'), $hoursNotice, $hoursNotice < 24 ? -4 : -3);
            }
            $this->decideRandomStatus($occurrence);
        }
    }

    private function addRandomCumulativeTardiness(User $user)
    {
        // 33% chance
        if (rand(0, 3) == 0) {
            $limit = rand(0, 3);

            for ($i = 0; $i < $limit; $i++) {
                $accumulatedOccurrences = array();
                // Accumulated occurrences
                for ($j = 0; $j < 5; $j++) {
                    // Accumulated ones have zero points
                    $newAccumulated = new TardinessOccurrence($user, 0, 1);
                    $accumulatedOccurrences[] = $newAccumulated;
                }
                $cumulativeOccurrence = new CumulativeTardinessOccurrence($user, -rand(0, 3), 5,
                    new \DateTime('yesterday'), new \DateTime(), $accumulatedOccurrences);

                $this->decideRandomStatus($cumulativeOccurrence);
            }
        }
    }

    private function decideRandomStatus(Occurrence $occurrence) {
        switch (rand(0, 3)) {
            case 0:
                $occurrence->approve();
                break;
            case 1:
                $occurrence->reject();
                break;
            default:
                break;
        }
    }
}