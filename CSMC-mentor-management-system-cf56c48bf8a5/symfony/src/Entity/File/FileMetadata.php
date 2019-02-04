<?php

namespace App\Entity\File;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class FileMetadata {
    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="File", inversedBy="metadata", cascade={"persist"})
     */
    private $file;

    /**
     * @ORM\Id()
     * @ORM\Column(type="string", name="`key`", length=32)
     */
    private $key;

    /**
     * @ORM\Column(type="string", name="`value`", length=2096)
     */
    private $value;

    public function __construct($key, $value) {
        $this->key = $key;
        $this->value = $value;
    }

    public function setFile($file) {
        $this->file = $file;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}