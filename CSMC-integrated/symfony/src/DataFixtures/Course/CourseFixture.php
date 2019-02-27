<?php


namespace App\DataFixtures\Course;


use App\DataFixtures\Misc\SemesterFixture;
use App\DataFixtures\User\UserFixture;
use App\Entity\Course\Course;
use App\Entity\Course\Department;
use App\Entity\Course\Section;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\DataFixtures\Course\DepartmentFixture;

class CourseFixture extends Fixture implements DependentFixtureInterface {
    const CS = 'cs';
    const SE = 'se';

    const CS_1336 = 'cs-1336';
    const CS_1337 = 'cs-1337';
    const CS_2305 = 'cs-2305';
    const CS_2336 = 'cs-2336';
    const CS_3305 = 'cs-3305';
    const CS_3340 = 'cs-3340';

    public function load(ObjectManager $manager) {
        // load departments
        $cs = new Department('Computer Science', 'CS');
        $manager->persist($cs);

        $se = new Department('Software Engineering', 'SE');
        $manager->persist($se);


        // load courses
        $courses = array();

        $pf = new Course($cs, '1336', 'Programming Fundamentals', true);
        $courses[] = $pf;
        $manager->persist($pf);

        $cs1 = new Course($cs, '1337', 'Computer Science I', true);
        $courses[] = $cs1;
        $manager->persist($cs1);

        $dm1 = new Course($cs, '2305', 'Discrete Math I', true);
        $courses[] = $dm1;
        $manager->persist($dm1);

        $cs2 = new Course($cs, '2336', 'Computer Science II', true);
        $courses[] = $cs2;
        $manager->persist($cs2);

        $dm2 = new Course($cs, '3305', 'Discrete Math II', true);
        $courses[] = $dm2;
        $manager->persist($dm2);

        $ca = new Course($cs, '3340', 'Computer Architecture', true);
        $courses[] = $ca;
        $manager->persist($ca);


        $semester = $this->getReference(SemesterFixture::ACTIVE);
        foreach($courses as $course) {
            $j = 0;
            for($i = 1; $i <= 5; $i++, $j += 10) {
                $n = str_pad($i - 1, 3, '0', STR_PAD_LEFT);
                $instructor = $this->getReference(UserFixture::INSTRUCTOR . '000' . $n);
                $section = new Section($course, $n, $semester, $instructor);

                $manager->persist($section);

                for($s = $j; $s <= $j + 10; $s++) {
                    $p = str_pad($s, 6, '0', STR_PAD_LEFT);
                    $student = $this->getReference(UserFixture::STUDENT . $p);
                    $section->enroll($student);
                }
            }
        }

        $manager->flush();

        // $this->addReference(self::CS, $cs); redundant creation from the DepartmentFixture
        // $this->addReference(self::SE, $se); redundant creation from the DepartmentFixture
        $this->addReference(self::CS_1336, $pf);
        $this->addReference(self::CS_1337, $cs1);
        $this->addReference(self::CS_2305, $dm1);
        $this->addReference(self::CS_2336, $cs2);
        $this->addReference(self::CS_3305, $dm2);
        $this->addReference(self::CS_3340, $ca);
    }

    public function getDependencies() {
        return array(
            SemesterFixture::class,
            UserFixture::class
        );
    }
}