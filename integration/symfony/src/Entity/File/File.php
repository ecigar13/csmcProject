<?php

namespace App\Entity\File;

use App\Annotation\Uploadable;
use App\DataTransferObject\FileData;
use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity
 * @ORM\Table(name="file")
 *
 * @Uploadable
 */
class File extends VirtualFile {

    /**
     * @ORM\OneToMany(targetEntity="FileMetadata", mappedBy="file", cascade={"persist"})
     */
    private $metadata;

    /**
     * TODO: check with Steven if adding "remove" is the right choice for recusive delete.
     * @ORM\ManyToOne(targetEntity="FileHash", cascade={"persist", "merge", "remove"})
     * @ORM\JoinColumn(name="hash_id", referencedColumnName="id")
     */
    private $hash;

    private $file;

    public function __construct(string $name, User $owner = null, FileHash $hash, array $metadata) {
        parent::__construct($name, $owner);
        $this->hash = $hash;
        $this->metadata = new ArrayCollection();
        foreach($metadata as $data) {
            $data->setFile($this);
            $this->metadata->add($data);
        }
    }

    /**
     * Get the file info from upload data. Create fie name, hash, get metadata of the file.
     * Create an entity with these info and return it.
     */
    public static function fromUploadData(FileData $fileData, EntityManagerInterface $entityManager, array $metadata = []) {
        $name = self::createName($fileData->file);
        $hash = self::createHash($fileData->file, $entityManager);
        if(!empty($metadata))
            $metadata = self::extractMetaData($fileData->file, $metadata);

        $file = new File($name, $fileData->user, $hash, $metadata);

        $file->file = $fileData->file;

        return $file;
    }

    /**
     * @param string $key
     *
     * @return string|null value
     */
    public function get(string $key) {
        foreach ($this->metadata as $m) {
            if ($m->getKey() === $key) {
                return $m->getValue();
            }
        }

        return null;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value) {
        foreach ($this->metadata as $m) {
            if ($m->getKey() === $key) {
                $m->setValue($value);
            }
        }
    }

    /**
     * @return string
     */
    public function getPhysicalName() {
        return $this->hash->getName();
    }

    public function getPhysicalDirectory() {
        return $this->hash->getDirectory();
    }

    public function getPhysicalPath() {
        return $this->hash->getFullPath();
    }

    /**
     * @return UploadedFile|null
     */
    public function getUploadedFile() {
        return $this->file;
    }

    private static function createName(UploadedFile $uploadedFile) {
        $name = $uploadedFile->getClientOriginalName();

        // find the position the extension starts
        $i = strpos($name, '.');
        $name = substr($name, 0, $i);
        $name = mb_convert_encoding($name, "UTF-8");
        return $name;
    }

    private static function createHash(UploadedFile $uploadedFile, EntityManagerInterface $entityManager) {
        $hash = sha1_file($uploadedFile->getRealPath());
        $size = filesize($uploadedFile->getRealPath());
        $extension = $uploadedFile->guessExtension();

        // $this->entityManager->getRepository(FileHash::class)

        $fileHash = $entityManager->getRepository(FileHash::class)
            ->findOneByPath($hash . '.' . $extension);

        if ($fileHash == null) {
            $fileHash = new FileHash($hash, $extension, $size);
        }

        return $fileHash;
    }

    private static function extractMetaData(UploadedFile $uploadedFile, array $metadata = null) {
        $return = array();

        $mime = $uploadedFile->getMimeType();
        $checkMime = $mime == $uploadedFile->getClientMimeType();

        $return[] = new FileMetadata('mime', $mime);

        $ext = $uploadedFile->guessExtension();
        $checkExt = $ext == $uploadedFile->getClientOriginalExtension();

        $return[] = new FileMetadata('extension', $ext);

        foreach ($metadata as $key => $value) {
             $return[] = new FileMetadata($key, $value);
        }

        return $return;
    }

    public function getHash(){
        return $this->hash;
    }
}
