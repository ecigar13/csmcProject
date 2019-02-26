<?php

namespace App\DataFixtures\User;

use App\Entity\User\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class RoleFixture extends Fixture {
    const ROLE_ADMIN = 'admin';
    const ROLE_MENTOR = 'mentor';
    const ROLE_INSTRUCTOR = 'instructor';
    const ROLE_STUDENT = 'student';
    const ROLE_DEVELOPER = 'developer';

    public function load(ObjectManager $manager) {
        $admin = new Role('admin');
        $manager->persist($admin);

        $mentor = new Role('mentor');
        $manager->persist($mentor);

        $instructor = new Role('instructor');
        $manager->persist($instructor);

        $student = new Role('student');
        $manager->persist($student);

        $developer = new Role('developer');
        $manager->persist($developer);

        $manager->flush();

        $this->addReference(self::ROLE_ADMIN, $admin);
        $this->addReference(self::ROLE_MENTOR, $mentor);
        $this->addReference(self::ROLE_INSTRUCTOR, $instructor);
        $this->addReference(self::ROLE_STUDENT, $student);
        $this->addReference(self::ROLE_DEVELOPER, $developer);
    }
}