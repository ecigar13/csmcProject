<?php

namespace App\DataFixtures\File;
use Doctrine\Common\Persistence\EntityManagerInterface;
use App\DataFixtures\Misc\SubjectFixture;
use App\Entity\User\Info\Specialty;
use App\Entity\User\User;
use App\Entity\File\Directory;
use App\Entity\File\FileHash;
use App\Entity\File\VirturalFile;
use App\DataFixtures\User\UserFixture;
use App\DataFixtures\User\RoleFixture;
use App\DataFixtures\File\ParentFixture;
use App\Form\Data\ProfileFormData;
use App\Form\Data\SpecialtyFormData;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class DirectoryFixture extends Fixture implements DependentFixtureInterface
{


    public function load(ObjectManager $manager)
    {
        $user = $this->getReference(UserFixture::INSTRUCTOR_00);
        $directory1  = new Directory("SE6301","/uploads/SE6301",$user);
        $directory2 = new Directory("SE6387","/uploads/SE6387",$user);
        $directory1->setParent($this->getReference(ParentFixture::UPLOADS));
        $directory2->setParent($this->getReference(ParentFixture::UPLOADS));

        $manager->persist($directory1);
        $manager->persist($directory2);
        $manager->flush();
        
    }

    public function getDependencies() {
        return array(
            UserFixture::class,
            ParentFixture::class
        );
    }
}