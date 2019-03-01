<?php


namespace App\DataFixtures\Course;


use App\Entity\Course\Course;
use App\Entity\Course\Department;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\DataFixtures\Course\DepartmentFixture;

class CourseFixture extends Fixture implements DependentFixtureInterface {

    const CS_1336 = '1336';
    const CS_1337 = '1337';
    const CS_2305 = '2305';
    const CS_2336 = '2336';
    const CS_3305 = '3305';
    const CS_3340 = '3340';

    public function load(ObjectManager $manager) {
        $cs = $this->getReference(DepartmentFixture::CS);
        $pf = new Course($cs, '1336', 'Programming Fundamentals', true);
        $manager->persist($pf);

        $cs1 = new Course($cs, '1337', 'Computer Science I', true);
        $manager->persist($cs1);

        $dm1 = new Course($cs, '2305', 'Discrete Math I', true);
        $manager->persist($dm1);

        $cs2 = new Course($cs, '2336', 'Computer Science II', true);
        $manager->persist($cs2);

        $dm2 = new Course($cs, '3305', 'Discrete Math II', true);
        $manager->persist($dm2);

        $ca = new Course($cs, '3340', 'Computer Architecture', true);
        $manager->persist($ca);

        $manager->flush();

        $this->addReference(self::CS_1336, $pf);
        $this->addReference(self::CS_1337, $cs1);
        $this->addReference(self::CS_2305, $dm1);
        $this->addReference(self::CS_2336, $cs2);
        $this->addReference(self::CS_3305, $dm2);
        $this->addReference(self::CS_3340, $ca);
    }

    public function getDependencies() {
        return array(
            DepartmentFixture::class
        );
    }
}