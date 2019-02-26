<?php


namespace App\DataFixtures\Misc;


use App\Entity\Misc\OperationHours;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class OperationHoursFixture extends Fixture {
    const SUNDAY = 'sunday';
    const MONDAY = 'monday';
    const TUESDAY = 'tuesday';
    const WEDNESDAY = 'wednesday';
    const THURSDAY = 'thursday';
    const FRIDAY = 'friday';
    const SATURDAY = 'saturday';

    public function load(ObjectManager $manager) {
        $ten_am = new \DateTime('10am');
        $noon = new \DateTime('12pm');
        $six_pm = new \DateTime('6pm');
        $eight_thirty_pm = new \DateTime('8:30pm');
        $ten_pm = new \DateTime('10pm');

        $sunday = new OperationHours('Sunday', $noon, $six_pm);
        $manager->persist($sunday);

        $monday = new OperationHours('Monday', $ten_am, $eight_thirty_pm);
        $manager->persist($monday);

        $tuesday = new OperationHours('Tuesday', $ten_am, $ten_pm);
        $manager->persist($tuesday);

        $wednesday = new OperationHours('Wednesday', $ten_am, $ten_pm);
        $manager->persist($wednesday);

        $thursday = new OperationHours('Thursday', $ten_am, $eight_thirty_pm);
        $manager->persist($thursday);

        $friday = new OperationHours('Friday', $ten_am, $six_pm);
        $manager->persist($friday);

        $saturday =new OperationHours('Saturday', $noon, $six_pm);
        $manager->persist($saturday);

        $manager->flush();

        $this->addReference(self::SUNDAY, $sunday);
        $this->addReference(self::MONDAY, $monday);
        $this->addReference(self::TUESDAY, $tuesday);
        $this->addReference(self::WEDNESDAY, $wednesday);
        $this->addReference(self::THURSDAY, $thursday);
        $this->addReference(self::FRIDAY, $friday);
        $this->addReference(self::SATURDAY, $saturday);
    }
}