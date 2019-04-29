<?php

namespace App\DataFixtures\File;
use Doctrine\Common\Persistence\EntityManagerInterface;
use App\DataFixtures\Misc\SubjectFixture;
use App\Entity\User\Info\Specialty;
use App\Entity\User\User;
use App\Entity\File\File;
use App\Entity\File\Link;
use App\Entity\File\Directory;
use App\Entity\File\FileHash;
use App\Entity\File\FileMetadata;
use App\Entity\File\VirturalFile;
use App\DataFixtures\User\UserFixture;
use App\DataFixtures\User\RoleFixture;
use App\DataFixtures\File\ParentFixture;
use App\DataFixtures\File\DirectoryFixture;
use App\DataTransferObject\FileData;
use App\Form\Data\ProfileFormData;
use App\Form\Data\SpecialtyFormData;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File as TestFile;

class FileFixture extends Fixture implements DependentFixtureInterface
{


    public function load(ObjectManager $manager)
    {
        $user = $this->getReference(UserFixture::INSTRUCTOR_00);
        //Get the file in the TestFile as Dummy data
        $uploadedFile = new TestFile("./TestFile/utd.jpg","utd.jpg");

        //Get Name
        $name = "utd.jpg";
        $i = strpos($name, '.');
        $name = substr($name, 0, $i);
        $name = mb_convert_encoding($name, "UTF-8");

        // echo "name" . $name;
        //Create Hash 
        $hash = sha1_file("./TestFile/utd.jpg");
        // echo "hash" .$hash;
        $size = filesize("./TestFile/utd.jpg");

        $extension = $uploadedFile->guessExtension();

        $fileHash = new FileHash($hash, $extension, $size);

        $metadata = array();

        $mime = $uploadedFile->getMimeType();
        $metadata[] = new FileMetadata('mime', $mime);
        $metadata[] = new FileMetadata('extension', $extension);

        $directory1 = $this->getReference("directory1");
        $path = $directory1->getPath();
        //echo "path".$path;
        $file = new File($name, $user, $fileHash, $metadata,$path);

        $file->setParent($this->getReference("directory1"));
        $manager->persist($file);
        $manager->flush();
        
    }


    public function guessExtension($file)
    {
        $guesser = ExtensionGuesser::getInstance();
        return $guesser->guess($file->type);
    }

    public function getDependencies() {
        return array(
            UserFixture::class,
            ParentFixture::class,
            DirectoryFixture::class
        );
    }
}