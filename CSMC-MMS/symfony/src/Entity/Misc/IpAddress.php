<?php

namespace App\Entity\Misc;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="ip_address")
 *
 * @UniqueEntity(
 *     fields={"address"},
 *     message="IP Address already exists!"
 * )
 */
class IpAddress {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="bigint", name="address", unique=true)
     *
     * @Assert\Regex(
     *     pattern="/(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])/",
     *     match=true,
     *     message="Invalid IP Address"
     * )
     */
    // TODO support ipv6 and ipv4
    private $address;

    /**
     * @ORM\ManyToOne(targetEntity="Room")
     * @ORM\JoinColumn(name="room_id", referencedColumnName="id")
     */
    private $room;

    /**
     * @ORM\Column(type="boolean", name="blocked")
     */
    private $blocked;

    /**
     * @ORM\OneToMany(targetEntity="Swipe", mappedBy="ip")
     */
    private $swipes;

    /**
     * Set address
     *
     * @param string $address
     *
     * @return IpAddress
     */
    public function setAddress($address) {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress() {
        return long2ip($this->address);
    }

    /**
     * Set room
     *
     * @param \App\Entity\Misc\Room $room
     *
     * @return IpAddress
     */
    public function setRoom(\App\Entity\Misc\Room $room = null) {
        $this->room = $room;

        return $this;
    }

    /**
     * Get room
     *
     * @return \App\Entity\Misc\Room
     */
    public function getRoom() {
        return $this->room;
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
     * Constructor
     */
    public function __construct() {
        $this->swipes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add swipe
     *
     * @param \App\Entity\Misc\Swipe $swipe
     *
     * @return IpAddress
     */
    public function addSwipe(\App\Entity\Misc\Swipe $swipe) {
        $this->swipes[] = $swipe;

        return $this;
    }

    /**
     * Remove swipe
     *
     * @param \App\Entity\Misc\Swipe $swipe
     */
    public function removeSwipe(\App\Entity\Misc\Swipe $swipe) {
        $this->swipes->removeElement($swipe);
    }

    /**
     * Get swipes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSwipes() {
        return $this->swipes;
    }
}
