<?php

namespace App\Entity\File;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class FileHash {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", length=191, name="size")
     */
    private $size;

    /**
     * @ORM\Column(type="string", length=191, name="path", unique=true)
     */
    private $path;

    public function __construct($hash, $extension, $size) {
        $this->path = $hash . '.' . $extension;
        $this->size = $size;
    }

    public function getDirectory() {
        // if one directory gets too large, consider further splitting, but probably not needed
        return substr($this->path, 0, 2) . '/' . substr($this->path, 2, 2);
    }

    public function getName() {
        return $this->path;
    }

    public function getFullPath() {
        return $this->getDirectory() . '/' . $this->path;
    }
    public function getPath(){
        return $this->path;
    }

    public function setPath(string $path){
        $this->path = $path;
    }
}