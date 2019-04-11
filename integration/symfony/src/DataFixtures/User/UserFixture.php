<?php

namespace App\DataFixtures\User;

use App\DataFixtures\Misc\SubjectFixture;
use App\Entity\User\Info\Specialty;
use App\Entity\User\User;
use App\DataFixtures\User\RoleFixture;
use App\Form\Data\ProfileFormData;
use App\Form\Data\SpecialtyFormData;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class UserFixture extends Fixture implements DependentFixtureInterface
{
    const INSTRUCTOR_00 = 'instructor_00';
    const INSTRUCTOR_01 = 'instructor_01';
    const INSTRUCTOR_02 = 'instructor_02';
    const INSTRUCTOR_03 = 'instructor_03';
    const INSTRUCTOR_04 = 'instructor_04';

    const MENTOR = 'mentor_';
    const STUDENT = 'student_';

    const ADMIN_00 = 'admin_00';

    const MENTOR_AMOUNT = 30;

    public function load(ObjectManager $manager)
    {
        // create mentors. These will load when doctrine:fixture:load runs.
        $mentors = array();

        $mentor_role = $this->getReference(RoleFixture::ROLE_MENTOR);

        for ($i = 0; $i < self::MENTOR_AMOUNT; $i++) {
            $p = str_pad($i, 6, '0', STR_PAD_LEFT);
            $u = new User('f_mentor_' . $p, 'l_mentor_' . $p, 'mxm' . $p);
            $u->addRole($mentor_role);
            $u->updateCardId($p . ":test", false);

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
	    $instructors[] = $u;
            //$instructors[$p] = $u;
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

        // create admin

        $admin_role = $this->getReference(RoleFixture::ROLE_ADMIN);
        $admin = new User('f_admin_000000', 'l_admin_000000', 'axa000000');
        $admin->addRole($admin_role);
        $manager->persist($admin);
        $manager->flush();

        $this->addReference(self::INSTRUCTOR_00, $instructors[0]);
        $this->addReference(self::INSTRUCTOR_01, $instructors[1]);
        $this->addReference(self::INSTRUCTOR_02, $instructors[2]);
        $this->addReference(self::INSTRUCTOR_03, $instructors[3]);
        $this->addReference(self::INSTRUCTOR_04, $instructors[4]);

        foreach ($mentors as $number => $mentor) {
            $this->addReference(self::MENTOR . $number, $mentor);
        }

        foreach ($students as $number => $student) {
            $this->addReference(self::STUDENT . $number, $student);
        }

        $this->addReference(self::ADMIN_00, $admin);
    }

    public function getDependencies() {
        return array(
            SubjectFixture::class,
            RoleFixture::class
        );
    }
}