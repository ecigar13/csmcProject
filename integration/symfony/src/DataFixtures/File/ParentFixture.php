<?php

namespace App\DataFixtures\File;

use App\DataFixtures\Misc\SubjectFixture;
use App\Entity\User\Info\Specialty;
use App\Entity\User\User;
use App\Entity\File\Directory;
use App\Entity\File\FileHash;
use App\Entity\File\VirturalFile;
use App\DataFixtures\User\UserFixture;
use App\DataFixtures\User\RoleFixture;
use App\Form\Data\ProfileFormData;
use App\Form\Data\SpecialtyFormData;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class ParentFixture extends Fixture implements DependentFixtureInterface
{
     public const ROOT = "root";

    public function load(ObjectManager $manager)
    {
        $user = $this->getReference(UserFixture::ADMIN_00);
        $directory  = new Directory("root",$user,"/root");
        $manager->persist($directory);
        $manager->flush();

        $this->addReference(self::ROOT,$directory);
        
    }

    public function getDependencies() {
        return array(
            UserFixture::class
        );
    }
}