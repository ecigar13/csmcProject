<?php


namespace App\DataFixtures\Course;


use App\DataFixtures\Misc\SemesterFixture;
use App\DataFixtures\Course\CourseFixture;
use App\DataFixtures\User\UserFixture;
use App\Entity\Course\Section;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class SectionFixture extends Fixture implements DependentFixtureInterface {
    const CS_3305 = 'cs_3305_section_';

    public function load(ObjectManager $manager) {
         $semester = $this->getReference(SemesterFixture::ACTIVE);

         // create CS 3305 sections
         $course = $this->getReference(CourseFixture::CS_3305);
         $instructor = $this->getReference(UserFixture::INSTRUCTOR_00);

         $cs_3305 = new ArrayCollection();
         for($i = 1; $i <= 5; $i++) {
             $n = str_pad($i, 3, '0', STR_PAD_LEFT);
             $section = new Section($course, $n, $semester, $instructor);

             $manager->persist($section);

             $cs_3305[$n] = $section;
         }

         $manager->flush();

         foreach($cs_3305 as $number => $section) {
             $this->addReference(self::CS_3305 . $number, $section);
         }
    }

    public function getDependencies() {
        return array(
            SemesterFixture::class,
            CourseFixture::class,
            UserFixture::class
        );
    }
}