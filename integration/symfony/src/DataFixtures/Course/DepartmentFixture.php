<?php


namespace App\DataFixtures\Course;


use App\Entity\Course\Department;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class DepartmentFixture extends Fixture {
    const CS = 'cs';
    const SE = 'se';

    public function load(ObjectManager $manager) {
        $cs = new Department('Computer Science', 'CS');
        $manager->persist($cs);

        $se = new Department('Software Engineering', 'SE');
        $manager->persist($se);

        $manager->flush();

        $this->addReference(self::CS, $cs);
        $this->addReference(self::SE, $se);
    }
}