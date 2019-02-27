<?php

namespace App\DataFixtures\Misc;


use App\Entity\Occurrence\OccurrenceType;
use App\Utils\AttendancePenaltyPersistenceManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class SettingsFixture extends Fixture
{
    public function load(ObjectManager $manager) {
        $penaltyManager = AttendancePenaltyPersistenceManager::loadModel($manager);
        $penaltyManager->createAbsenceWithoutNoticePenalty(-5);
        $penaltyManager->persistModel($manager);

        $occurrenceTypes = [
            new OccurrenceType(-4, "Rude to student"),
            new OccurrenceType(-3, "Left shift early"),
            new OccurrenceType(-2, "Sassiness"),
            new OccurrenceType(5, "Above and beyond"),
            new OccurrenceType(3, "Staying late to help")
        ];

        foreach ($occurrenceTypes as $occurrenceType) {
            $manager->persist($occurrenceType);
        }

        $manager->flush();
    }

}