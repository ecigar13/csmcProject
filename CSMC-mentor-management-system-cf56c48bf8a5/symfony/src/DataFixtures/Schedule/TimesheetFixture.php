<?php

namespace App\DataFixtures\Schedule;

use App\DataFixtures\User\UserFixture;
use App\Entity\Schedule\Timesheet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class TimesheetFixture extends Fixture implements DependentFixtureInterface {
    public function load(ObjectManager $manager) {
         $clocked_in = 10;

         for ($i = 0; $i < $clocked_in; $i++) {
             $n = str_pad($i, 6, '0', STR_PAD_LEFT);
             $user = $this->getReference(UserFixture::MENTOR . $n);

             $ts = new Timesheet($user);

             $manager->persist($ts);
         }

         $manager->flush();
    }

    function getDependencies() {
        return [
            UserFixture::class
        ];
    }
}