<?php


namespace App\DataFixtures\Misc;


use App\Entity\Misc\Semester;
use App\Entity\Misc\SemesterSeason;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class SemesterFixture extends Fixture {

    const ACTIVE = 'active';

    public function load(ObjectManager $manager) {
        $start = new \DateTime();
        $start->setDate($start->format('Y'), 1, 1);
        $end = new \DateTime();
        $end->setDate($end->format('Y'), 12, 31);

        $semester = new Semester(Semester::SEASON_DEV, (int) $start->format('Y'), $start, $end, true);

        $manager->persist($semester);
        $manager->flush();

        $this->addReference(self::ACTIVE, $semester);
    }
}