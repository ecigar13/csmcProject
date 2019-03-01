<?php

namespace App\Entity\Misc;


use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="visit")
 */
class Visit {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="ip", length=45)
     */
    private $ip;

    /**
     * @ORM\Column(type="string", name="browser", length=255)
     */
    private $browser;

    /**
     * @ORM\Column(type="datetime", name="timestamp")
     */
    private $timestamp;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User", inversedBy="visits", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Misc\PageVisit", mappedBy="visit", cascade={"persist"})
     */
    private $pageVisits;

    public function __toString() {
        return $this->id;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->pageVisits = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return guid
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set ip
     *
     * @param string $ip
     *
     * @return Visit
     */
    public function setIp($ip) {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * Set browser
     *
     * @param string $browser
     *
     * @return Visit
     */
    public function setBrowser($browser) {
        $this->browser = $browser;

        return $this;
    }

    /**
     * Get browser
     *
     * @return string
     */
    public function getBrowser() {
        return $this->browser;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     *
     * @return Visit
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
     * Set user
     *
     * @param \App\Entity\User\User $user
     *
     * @return Visit
     */
    public function setUser(\App\Entity\User\User $user = null) {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \App\Entity\User\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Add pageVisit
     *
     * @param \App\Entity\Misc\PageVisit $pageVisit
     *
     * @return Visit
     */
    public function addPageVisit(\App\Entity\Misc\PageVisit $pageVisit) {
        $this->pageVisits[] = $pageVisit;

        return $this;
    }

    /**
     * Remove pageVisit
     *
     * @param \App\Entity\Misc\PageVisit $pageVisit
     */
    public function removePageVisit(\App\Entity\Misc\PageVisit $pageVisit) {
        $this->pageVisits->removeElement($pageVisit);
    }

    /**
     * Get pageVisits
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPageVisits() {
        return $this->pageVisits;
    }
}
