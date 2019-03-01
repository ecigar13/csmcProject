<?php

namespace App\Entity\Session;


use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity
 * @ORM\Table(name="registration")
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Registration {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @Serializer\Expose()
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *
     * @Serializer\Expose()
     */
    private $user;

    /**
     * @ORM\Column(type="datetime", name="time")
     */
    private $time;

    /**
     * @ORM\ManyToOne(targetEntity="SessionTimeSlot", inversedBy="registrations")
     * @ORM\JoinColumn(name="timeslot_id", referencedColumnName="id")
     */
    private $timeSlot;

    public function __construct(SessionTimeSlot $timeSlot, User $user) {
        $this->timeSlot = $timeSlot;
        $this->user = $user;
        $this->time = new \DateTime();
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
     * Set time
     *
     * @param \DateTime $time
     *
     * @return Registration
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
     * Set user
     *
     * @param \App\Entity\User\User $user
     *
     * @return Registration
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
     * Set timeslot
     *
     * @param \App\Entity\Session\TimeSlot $timeSlot
     *
     * @return Registration
     */
    public function setTimeSlot(\App\Entity\Session\TimeSlot $timeSlot = null) {
        $this->timeSlot = $timeSlot;

        return $this;
    }

    /**
     * Get timeslot
     *
     * @return \App\Entity\Session\TimeSlot
     */
    public function getTimeSlot() {
        return $this->timeSlot;
    }
}
