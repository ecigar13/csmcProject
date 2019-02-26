<?php

namespace App\DataFixtures\Misc;

use App\Entity\Misc\Room;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class RoomFixture extends Fixture {
    const ECSS_4415 = '4.415';

    public function load(ObjectManager $manager) {
        $room = new Room('ECSS', 4, 415, '', 50, true);
        $manager->persist($room);
        $manager->flush();
        $this->addReference(self::ECSS_4415, $room);
    }
}