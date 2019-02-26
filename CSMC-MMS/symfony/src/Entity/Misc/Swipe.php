<?php

namespace App\Entity\Misc;


use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="swipe")
 */
class Swipe {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="IpAddress", inversedBy="swipes")
     * @ORM\JoinColumn(name="ip_id", referencedColumnName="id")
     */
    private $ip;

    /**
     * @ORM\Column(type="datetime", name="time")
     */
    private $time;

    /**
     * @ORM\Column(type="boolean", name="valid")
     */
    private $valid;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\Column(type="boolean", name="legacy", nullable=true)
     */
    private $legacy;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set time
     *
     * @param \DateTime $time
     *
     * @return Swipe
     */
    public function setTime($time) {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time
     *
     * @return \DateTime
     */
    public function getTime() {
        return $this->time;
    }

    /**
     * Set valid
     *
     * @param boolean $valid
     *
     * @return Swipe
     */
    public function setValid($valid) {
        $this->valid = $valid;

        return $this;
    }

    /**
     * Get valid
     *
     * @return boolean
     */
    public function getValid() {
        return $this->valid;
    }

    /**
     * Set ip
     *
     * @param \App\Entity\Misc\IpAddress $ip
     *
     * @return Swipe
     */
    public function setIp(\App\Entity\Misc\IpAddress $ip = null) {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return \App\Entity\Misc\IpAddress
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * Set user
     *
     * @param \App\Entity\User\User $user
     *
     * @return Swipe
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
     * Set legacy
     *
     * @param boolean $legacy
     *
     * @return Swipe
     */
    public function setLegacy($legacy)
    {
        $this->legacy = $legacy;

        return $this;
    }

    /**
     * Get legacy
     *
     * @return boolean
     */
    public function getLegacy()
    {
        return $this->legacy;
    }
}
