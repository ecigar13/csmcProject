<?php

namespace App\Entity\Misc;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="semester_season")
 */
class SemesterSeason {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="season", length=6)
     */
    private $name;

    /**
     * @ORM\Column(type="string", name="prefix", length=2)
     */
    private $prefix;

    public function __construct() {
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
     * @return SemesterSeason
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
     * Set startMonth
     *
     * @param integer $startMonth
     *
     * @return SemesterSeason
     */
    public function setStartMonth($startMonth) {
        $this->startMonth = $startMonth;

        return $this;
    }

    /**
     * Get startMonth
     *
     * @return integer
     */
    public function getStartMonth() {
        return $this->startMonth;
    }

    /**
     * Set endMonth
     *
     * @param integer $endMonth
     *
     * @return SemesterSeason
     */
    public function setEndMonth($endMonth) {
        $this->endMonth = $endMonth;

        return $this;
    }

    /**
     * Get endMonth
     *
     * @return integer
     */
    public function getEndMonth() {
        return $this->endMonth;
    }

    /**
     * Set prefix
     *
     * @param string $prefix
     *
     * @return SemesterSeason
     */
    public function setPrefix($prefix) {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get prefix
     *
     * @return string
     */
    public function getPrefix() {
        return $this->prefix;
    }
}
