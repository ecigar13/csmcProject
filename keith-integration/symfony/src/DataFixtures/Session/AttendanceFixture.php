<?php


namespace App\DataFixtures\Session;


use App\DataFixtures\Course\CourseFixture;
use App\DataFixtures\Course\SectionFixture;
use App\DataFixtures\User\UserFixture;
use App\Entity\Session\WalkInActivity;
use App\Entity\Session\WalkInAttendance;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class AttendanceFixture extends Fixture implements DependentFixtureInterface {
    public function load(ObjectManager $manager) {
         // // make walk-ins
         // $checked_in = 15;
         //
         // $activity = new WalkInActivity('Test Activity');
         // $manager->persist($activity);
         //
         // for($i = 0; $i < $checked_in; $i++) {
         //     $n = str_pad($i, 6, '0', STR_PAD_LEFT);
         //
         //     $user = $this->getReference(UserFixture::STUDENT . $n);
         //
         //     $attendance = new WalkInAttendance($user, $activity, 'test topic', $this->getReference(CourseFixture::CS_3305), $this->getReference(SectionFixture::CS_3305 . '001'));
         //     $manager->persist($attendance);
         // }
         //
         // $manager->flush();
    }

    public function getDependencies() {
        return [
            UserFixture::class,
            CourseFixture::class
        ];
    }

}