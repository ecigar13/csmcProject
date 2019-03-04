<?php

namespace App\Entity\Misc;


use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity
 * @ORM\Table(name="subject")
 */
class Subject {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="name", length=32, unique=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", name="abbreviation", length=8, unique=true, nullable=true)
     */
    private $abbreviation;

    /**
     * @ORM\Column(type="boolean")
     */
    private $showOnCalendar;

    /**
     * @ORM\Column(type="integer", name="`order`", unique=true, nullable=true)
     */
    private $order;

    /**
     * @ORM\Column(type="string", name="color", length=7, unique=true, nullable=true)
     */
    private $color;

    public function __construct(string $name, string $abbreviation, bool $showOnCalendar = null, string $color = null, int $order = null) {
        $this->name = $name;
        $this->abbreviation = $abbreviation;
        $this->showOnCalendar = $showOnCalendar ?? false;
        $this->color = $color;
        $this->order = $order;
    }

    public function __toString() {
        return strval($this->name);
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Subject
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set abbreviation
     *
     * @param string $abbreviation
     *
     * @return Subject
     */
    public function setAbbreviation($abbreviation) {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    /**
     * Get abbreviation
     *
     * @return string
     */
    public function getAbbreviation() {
        return $this->abbreviation;
    }

    /**
     * Set showOnCalendar
     *
     * @param boolean $showOnCalendar
     *
     * @return Subject
     */
    public function setShowOnCalendar($showOnCalendar) {
        $this->showOnCalendar = $showOnCalendar;

        return $this;
    }

    /**
     * Get showOnCalendar
     *
     * @return boolean
     */
    public function getShowOnCalendar() {
        return $this->showOnCalendar;
    }

    /**
     * Set color
     *
     * @param string $color
     *
     * @return Subject
     */
    public function setColor($color) {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     * @return string
     */
    public function getColor() {
        return $this->color;
    }

    /**
     * Set order
     *
     * @param integer $order
     *
     * @return Subject
     */
    public function setOrder($order) {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return integer
     */
    public function getOrder() {
        return $this->order;
    }
}
