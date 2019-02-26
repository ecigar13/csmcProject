<?php

namespace App\Entity\Session;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="session_type")
 */
class SessionType {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=8, name="name")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=7, name="color")
     */
    private $color;

    public function __construct(string $name, string $color) {
        $this->name = $name;
        $this->color = $color;
    }

    public function getName() {
        return $this->name;
    }

    public function getColor() {
        return $this->color;
    }

    public function updateColor(string $color) {
        $this->color = $color;
        return $this;
    }
}