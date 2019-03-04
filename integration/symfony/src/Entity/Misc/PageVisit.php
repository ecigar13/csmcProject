<?php

namespace App\Entity\Misc;


use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="page_visit")
 */
class PageVisit {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Misc\Visit", inversedBy="pageVisits", cascade={"persist"})
     * @ORM\JoinColumn(name="visit_id", referencedColumnName="id")
     */
    private $visit;

    /**
     * @ORM\Column(type="string", name="route", length=255)
     */
    private $route;

    /**
     * @ORM\Column(type="datetime", name="timestamp")
     */
    private $timestamp;

    /**
     * Get id
     *
     * @return guid
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set route
     *
     * @param string $route
     *
     * @return PageVisit
     */
    public function setRoute($route) {
        $this->route = $route;

        return $this;
    }

    /**
     * Get route
     *
     * @return string
     */
    public function getRoute() {
        return $this->route;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     *
     * @return PageVisit
     */
    public function setTimestamp($timestamp) {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return \DateTime
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Set visit
     *
     * @param \App\Entity\Misc\Visit $visit
     *
     * @return PageVisit
     */
    public function setVisit(\App\Entity\Misc\Visit $visit = null) {
        $this->visit = $visit;

        return $this;
    }

    /**
     * Get visit
     *
     * @return \App\Entity\Misc\Visit
     */
    public function getVisit() {
        return $this->visit;
    }
}
