<?php

namespace App\DataFixtures\Misc;

use App\Entity\Misc\Subject;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class SubjectFixture extends Fixture {
    const DISCRETE_MATH = 'discrete_math';
    const COMPUTER_ARCHITECTURE = 'computer_architecture';
    const CPP = 'c/c++';
    const JAVA = 'java';
    const DATA_STRUCTURES = 'data_structures';
    const UNIX = 'unix';
    const AUTOMATA_THEORY = 'automata_theory';


    public function load(ObjectManager $manager) {
        $java = new Subject('Java', 'Java', true, '#4B3621', 1);
        $manager->persist($java);

        $cpp = new Subject('C/C++', 'C/C++', true, '#232B2B', 2);
        $manager->persist($cpp);

        $dm = new Subject('Discrete Math', 'DM', true, '#36454F', 3);
        $manager->persist($dm);

        $ca = new Subject('Computer Architecture', 'CArch', true, '#414A4C', 4);
        $manager->persist($ca);

        $ds = new Subject('Data Structures', 'DS');
        $manager->persist($ds);

        $unix = new Subject('Unix', 'Unix');
        $manager->persist($unix);

        $at = new Subject('Automata Theory', 'AT');
        $manager->persist($at);

        $manager->flush();

        $this->addReference(self::JAVA, $java);
        $this->addReference(self::CPP, $cpp);
        $this->addReference(self::DISCRETE_MATH, $dm);
        $this->addReference(self::COMPUTER_ARCHITECTURE, $ca);
        $this->addReference(self::DATA_STRUCTURES, $ds);
        $this->addReference(self::UNIX, $unix);
        $this->addReference(self::AUTOMATA_THEORY, $at);
    }
}