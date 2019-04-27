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
use App\Entity\File\FilePermissions;
use App\DataFixtures\User\UserFixture;
use App\DataFixtures\User\RoleFixture;
use App\DataFixtures\File\LinkFixture;
use App\DataFixtures\File\ParentFixture;
use App\DataFixtures\File\DirectoryFixture;
use App\Form\Data\ProfileFormData;
use App\Form\Data\SpecialtyFormData;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class FilePermissionFixture extends Fixture implements DependentFixtureInterface
{


    public function load(ObjectManager $manager)
    {
        $student1 = $this->getReference("student_000001");
        $student2 = $this->getReference("student_000002");
        $mentor1 = $this->getReference(UserFixture::INSTRUCTOR_00);
        
        $directory1 =$this->getReference("directory1");
        $directory2 =$this->getReference("directory2");
        
        $linkpermission1 = new FilePermissions($student1,true,false);
        $linkpermission1->setVirtualFile($directory1);
        $manager->persist($linkpermission1);

        $instructor_permission = new FilePermissions($mentor1,true,true);
        $instructor_permission->setVirtualFile($directory1);
        $manager->persist($instructor_permission);

        $linkpermission2 = new FilePermissions($student2,true,false);
        $linkpermission2->setVirtualFile($directory2);
        $manager->persist($linkpermission2);

        $instructor_permission2 = new FilePermissions($mentor1,true,true);
        $instructor_permission2->setVirtualFile($directory2);
        $manager->persist($instructor_permission2);

        $manager->flush();
        
    }

    public function getDependencies() {
        return array(
            UserFixture::class,
            ParentFixture::class,
            DirectoryFixture::class,
            LinkFixture::class
        );
    }
}