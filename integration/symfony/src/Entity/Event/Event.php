<?php

namespace App\Entity\Event;

use App\Entity\Misc\Room;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Event\EventRepository")
 * @ORM\Table(name="event")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
abstract class Event {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $startTime;

    /**
     * @ORM\Column(type="datetime")
     */
    private $endTime;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Misc\Room")
     * @ORM\JoinColumn(name="room_id", referencedColumnName="id")
     */
    private $location;

    /**
     * @ORM\Column(type="string", length=7, name="color", nullable=true)
     */
    private $color;

    public function __construct(Room $location, \DateTime $start, \DateTime $end) {
        $this->location = $location;
        $this->startTime = $start;
        $this->endTime = $end;
    }

    public function isAllDay() {
        return false;
    }

    abstract public function getName();

    public function getStartTime() {
        return $this->startTime;
    }

    public function getEndTime() {
        return $this->endTime;
    }

    public function updateTime(\DateTime $start, \DateTime $end) {
        $this->startTime = $start;
        $this->endTime = $end;
    }

    public function getLocation() {
        return $this->location;
    }

    public function updateLocation(Room $location) {
        $this->location = $location;
    }

    public function getId() {
        return $this->id;
    }

    public function getColor() {
        return $this->color;
    }
}