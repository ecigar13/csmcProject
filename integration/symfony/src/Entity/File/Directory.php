<?php

namespace App\Entity\File;

use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="directory")
 */
class Directory extends VirtualFile {

    private $directory;

    /**
     * @ORM\Column(type="string", name="path", length=4000)
     */
    private $path;

    public function __construct(string $name, string $path,User $owner) {
        parent::__construct($name, $owner);
        $this->path=$path;
    }

    
    /**
     * Get the file info from upload data. Create fie name, hash, get metadata of the file.
     * Create an entity with these info and return it.
     */
    // public static function fromUploadData(FileData $fileData, EntityManagerInterface $entityManager) {
    //     $name = self::createName($fileData->file);
    //     if(!empty($metadata))
    //         $metadata = self::extractMetaData($fileData->file, $metadata);

    //     $file = new File($name, $fileData->user, $hash, $metadata);

    //     $file->file = $fileData->file;

    //     return $file;
    // }

    // /**
    //  * @param string $key
    //  *
    //  * @return string|null value
    //  */
    // public function get(string $key) {
    //     foreach ($this->metadata as $m) {
    //         if ($m->getKey() === $key) {
    //             return $m->getValue();
    //         }
    //     }

    //     return null;
    // }

    // /**
    //  * @param string $key
    //  * @param mixed $value
    //  */
    // public function set(string $key, $value) {
    //     foreach ($this->metadata as $m) {
    //         if ($m->getKey() === $key) {
    //             $m->setValue($value);
    //         }
    //     }
    // }

    // /**
    //  * @return string
    //  */
    // public function getPhysicalName() {
    //     return $this->hash->getName();
    // }

    // public function getPhysicalDirectory() {
    //     return $this->hash->getDirectory();
    // }

    // public function getPhysicalPath() {
    //     return $this->hash->getFullPath();
    // }

    // /**
    //  * @return UploadedFile|null
    //  */
    // public function getUploadedFile() {
    //     return $this->file;
    // }

    // private static function createName(UploadedFile $uploadedFile) {
    //     $name = $uploadedFile->getClientOriginalName();

    //     // find the position the extension starts
    //     $i = strpos($name, '.');
    //     $name = substr($name, 0, $i);
    //     $name = mb_convert_encoding($name, "UTF-8");
    //     return $name;
    // }
}
