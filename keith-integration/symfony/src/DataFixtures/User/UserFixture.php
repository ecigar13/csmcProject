<?php

namespace App\DataFixtures\User;

use App\DataFixtures\Misc\SubjectFixture;
use App\Entity\User\User;
use App\DataFixtures\User\RoleFixture;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class UserFixture extends Fixture implements DependentFixtureInterface {
    const INSTRUCTOR = 'instructor_';
    const MENTOR = 'mentor_';
    const STUDENT = 'student_';

    public function load(ObjectManager $manager) {
        // create mentors
        $mentors = array();

        $mentor_role = $this->getReference(RoleFixture::ROLE_MENTOR);

        for ($i = 0; $i < 20; $i++) {
            $p = str_pad($i, 6, '0', STR_PAD_LEFT);
            $u = new User('f_mentor_' . $p, 'l_mentor_' . $p, 'mxm' . $p);
            $u->addRole($mentor_role);

            $u->updateSpecialty($this->getReference(SubjectFixture::JAVA), rand(1, 5));
            $u->updateSpecialty($this->getReference(SubjectFixture::CPP), rand(1, 5));
            $u->updateSpecialty($this->getReference(SubjectFixture::DISCRETE_MATH), rand(1, 5));
            $u->updateSpecialty($this->getReference(SubjectFixture::COMPUTER_ARCHITECTURE), rand(1, 5));

            $manager->persist($u);

            $mentors[$p] = $u;
        }

        // create instructors
        $instructors = array();

        $instructor_role = $this->getReference(RoleFixture::ROLE_INSTRUCTOR);

        for ($i = 0; $i < 5; $i++) {
            $p = str_pad($i, 6, '0', STR_PAD_LEFT);
            $u = new User('f_inst_' . $p, 'l_inst_' . $p, 'ixi' . $p);
            $u->addRole($instructor_role);
            $manager->persist($u);

            $instructors[$p] = $u;
        }

        // create students
        $students = array();

        $student_role = $this->getReference(RoleFixture::ROLE_STUDENT);

        for ($i = 0; $i < 100; $i++) {
            $p = str_pad($i, 6, '0', STR_PAD_LEFT);
            $u = new User('f_student_' . $p, 'l_student_' . $p, 'sxs' . $p);
            $u->addRole($student_role);
            $manager->persist($u);

            $students[$p] = $u;
        }

        $manager->flush();

        foreach($instructors as $number => $instructor) {
            $this->addReference(self::INSTRUCTOR . $number, $instructor);
        }

        foreach ($mentors as $number => $mentor) {
            $this->addReference(self::MENTOR . $number, $mentor);
        }

        foreach ($students as $number => $student) {
            $this->addReference(self::STUDENT . $number, $student);
        }
    }

    public function getDependencies() {
        return array(
            SubjectFixture::class,
            RoleFixture::class
        );
    }
}