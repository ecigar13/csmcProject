<?php

namespace App\Entity\Session;

use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="scheduled_session_attendance")
 */
class ScheduledSessionAttendance extends SessionAttendance {
    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Misc\Swipe", cascade={"persist"})
     * @ORM\JoinTable(name="scheduled_session_swipes",
     *     joinColumns={@ORM\JoinColumn(name="attendance_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="swipe_id", referencedColumnName="id", unique=true)}
     *     )
     */
    private $swipes;

    public function __construct(User $user, SessionTimeSlot $timeSlot, \DateTime $dateTime = null) {
        parent::__construct($user, $timeSlot, $dateTime);
    }

    /**
     * Add swipe
     *
     * @param \App\Entity\Misc\Swipe $swipe
     *
     * @return ScheduledSessionAttendance
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
