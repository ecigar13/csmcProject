<?php

namespace App\DataFixtures\File;
use Doctrine\Common\Persistence\EntityManagerInterface;
use App\DataFixtures\Misc\SubjectFixture;
use App\Entity\User\Info\Specialty;
use App\Entity\User\User;
use App\Entity\File\Link;
use App\Entity\File\Directory;
use App\Entity\File\FileHash;
use App\Entity\File\VirturalFile;
use App\DataFixtures\User\UserFixture;
use App\DataFixtures\User\RoleFixture;
use App\DataFixtures\File\ParentFixture;
use App\DataFixtures\File\DirectoryFixture;
use App\Form\Data\ProfileFormData;
use App\Form\Data\SpecialtyFormData;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LinkFixture extends Fixture implements DependentFixtureInterface
{


    public function load(ObjectManager $manager)
    {
        $user = $this->getReference(UserFixture::INSTRUCTOR_00);
        $link1  = new Link("Requirement_Enigneering",$user,"https://www.geeksforgeeks.org/software-engineering-requirements-engineering-process/");
        $link2 = new Link("Software_engineer",$user,"https://en.wikipedia.org/wiki/Software_engineer");
        $link1->setParent($this->getReference("directory1"));
        $link2->setParent($this->getReference("directory2"));
        // $file->file_get_contents("test_pdf");
        $manager->persist($link1);
        $manager->persist($link2);
        $this->addReference("link1",$link1);
        $this->addReference("link2",$link2);
        $manager->flush();
        
    }

    public function getDependencies() {
        return array(
            UserFixture::class,
            ParentFixture::class,
            DirectoryFixture::class
        );
    }
}