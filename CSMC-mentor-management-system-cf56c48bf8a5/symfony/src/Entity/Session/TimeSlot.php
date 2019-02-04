<?php

namespace App\Entity\Session;

use App\Entity\Event\Event;
use App\Entity\Misc\Room;
use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Session\TimeSlotRepository")
 * @ORM\Table(name="timeslot")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
abstract class TimeSlot extends Event {

    /**
     * @ORM\OneToMany(targetEntity="SessionAttendance", mappedBy="timeSlot", cascade={"persist", "remove"})
     */
    protected $attendances;

    public function hasAttended($user) {
        foreach ($this->getAttendances() as $attendance) {
            if ($attendance->getUser()->getId() == $user->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Constructor
     */
    public function __construct(Room $location, \DateTime $start, \DateTime $end) {
        parent::__construct($location, $start, $end);

        $this->attendances = new ArrayCollection();
    }

    /**
     * Add attendance
     *
     * @param \App\Entity\Session\ScheduledSessionAttendance $attendance
     *
     * @return TimeSlot
     */
    public function addAttendance(\App\Entity\Session\ScheduledSessionAttendance $attendance) {
        $this->attendances[] = $attendance;

        return $this;
    }

    /**
     * Remove attendance
     *
     * @param \App\Entity\Session\ScheduledSessionAttendance $attendance
     */
    public function removeAttendance(\App\Entity\Session\ScheduledSessionAttendance $attendance) {
        $this->attendances->removeElement($attendance);
    }

    /**
     * Get attendances
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttendances() {
        return $this->attendances;
    }

    public abstract function checkIn(User $user, \DateTime $dateTime = null);

    public function checkOut(User $user, \DateTime $dateTime = null, array $mentors = null) {
        foreach ($this->attendances as $attendance) {
            if($attendance->getUser() == $user) {
                $attendance->checkOut($mentors, $dateTime);

                return $attendance;
            }
        }

        return null;
    }
}
