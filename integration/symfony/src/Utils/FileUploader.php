<?php

namespace App\Utils;

use App\Entity\File\File;
use App\Entity\File\FileHash;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Psr\Log\LoggerInterface;

class FileUploader {
    private $tokenStorage;
    private $uploadDirectory;

    public function __construct(TokenStorageInterface $tokenStorage, $uploadDirectory) {
        $this->uploadDirectory = $uploadDirectory;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * File is saved to disk first. Create a new Filesystem, make hash of the file. Then make directory for the file and move it. It's not a direct upload.
     * The $entity is most likely a File object, but could be other things.
     */
    public function upload($entity) {
        $uploadedFile = $entity->getUploadedFile();

        $fileSystem = new Filesystem();
        if(!$fileSystem->exists($this->uploadDirectory)) {
            $fileSystem->mkdir($this->uploadDirectory);
        }

        $dir = $this->uploadDirectory . '/' . $entity->getPhysicalDirectory();
        $name = $entity->getPhysicalName();

        //move tmp file to path
        $uploadedFile->move($dir, $name);
    }
}
