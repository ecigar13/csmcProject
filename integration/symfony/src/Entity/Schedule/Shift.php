<?php

namespace App\Entity\Schedule;

use App\Entity\Misc\Room;
use App\Entity\Misc\Subject;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Schedule\ShiftRepository")
 * @ORM\Table(name="shift")
 */
class Shift {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="time", name="start_time")
     */
    private $startTime;

    /**
     * @ORM\Column(type="time", name="end_time")
     */
    private $endTime;

    /**
     * @ORM\Column(type="smallint", name="day")
     */
    private $day;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Misc\Room")
     * @ORM\JoinColumn(name="room_id", referencedColumnName="id")
     */
    private $room;

    /**
     * @ORM\OneToMany(targetEntity="ShiftSubject", mappedBy="shift", cascade={"persist"})
     */
    private $subjects;

    /**
     * @ORM\ManyToOne(targetEntity="Schedule", inversedBy="shifts")
     * @ORM\JoinColumn(name="schedule_id", referencedColumnName="id")
     */
    private $schedule;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User")
     * @ORM\JoinColumn(name="shift_leader_id", referencedColumnName="id")
     */
    private $shiftLeader;

    /**
     * Constructor
     */
    public function __construct(Schedule $schedule, Room $location, \DateTime $startTime, \DateTime $endTime, int $day) {
        $this->schedule = $schedule;
        $this->room = $location;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->day = $day;
        $this->subjects = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set startTime
     *
     * @param \DateTime $startTime
     *
     * @return Shift
     */
    public function setStartTime($startTime) {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return \DateTime
     */
    public function getStartTime() {
        return $this->startTime;
    }

    /**
     * Set endTime
     *
     * @param \DateTime $endTime
     *
     * @return Shift
     */
    public function setEndTime($endTime) {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime
     */
    public function getEndTime() {
        return $this->endTime;
    }

    /**
     * Set day
     *
     * @param integer $day
     *
     * @return Shift
     */
    public function setDay($day) {
        $this->day = $day;

        return $this;
    }

    /**
     * Get day
     *
     * @return integer
     */
    public function getDay() {
        return $this->day;
    }

    /**
     * Set room
     *
     * @param \App\Entity\Misc\Room $room
     *
     * @return Shift
     */
    public function setRoom(Room $room = null) {
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

    public function addMentor(Subject $subject, User $mentor) {
        foreach($this->subjects as $shift_subject) {
            if($shift_subject->getSubject() == $subject) {
                $shift_subject->addMentor($mentor);

                return $this;
            }
        }

        return $this;
    }

    public function removeMentor(Subject $subject, User $mentor) {
        foreach($this->subjects as $shift_subject) {
            if($shift_subject->getSubject() == $subject) {
                $shift_subject->removeMentor($mentor);

                return $this;
            }
        }

        return $this;
    }

    /**
     * Add subject
     *
     * @param \App\Entity\Schedule\ShiftSubject $subject
     * @param int $max
     *
     * @return Shift
     */
    public function addSubject(Subject $subject, int $max) {
        $shift_subject = new ShiftSubject($subject, $max);
        $shift_subject->setShift($this);
        $this->subjects[] = $shift_subject;

        return $this;
    }

    /**
     * Remove subject
     *
     * @param \App\Entity\Schedule\ShiftSubject $subject
     */
    public function removeSubject(ShiftSubject $subject) {
        $this->subjects->removeElement($subject);
    }

    /**
     * Get subjects
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubjects() {
        return $this->subjects;
    }

    /**
     * Set schedule
     *
     * @param \App\Entity\Schedule\Schedule $schedule
     *
     * @return Shift
     */
    public function setSchedule(Schedule $schedule = null) {
        $this->schedule = $schedule;

        return $this;
    }

    /**
     * Get schedule
     *
     * @return \App\Entity\Schedule\Schedule
     */
    public function getSchedule() {
        return $this->schedule;
    }

    /**
     * Set shiftLeader
     *
     * @param \App\Entity\User\User $shiftLeader
     *
     * @return Shift
     */
    public function assignShiftLeader(User $shiftLeader = null) {
        $this->shiftLeader = $shiftLeader;

        return $this;
    }

    /**
     * Get shiftLeader
     *
     * @return \App\Entity\User\User
     */
    public function getShiftLeader() {
        return $this->shiftLeader;
    }

    public function getMentors() {
        $mentors = new ArrayCollection();
        foreach($this->subjects as $subject) {
            foreach($subject->getMentors() as $mentor) {
                $mentors->add($mentor);
            }
        }
        return $mentors;
    }
    /**
     * Returns the amount of minutes of tardiness that a mentor would accrue signing in at the provided time.
     *
     * @param \DateTime $signInDateTime Must happen between this shift's start time and end time (inclusive). The date
     * part will be ignored.
     * @return int
     */
    public function calculateTardinessMinutesForSignIn(\DateTime $signInDateTime)
    {
        // Make sure we are using only the time
        $startTime = new \DateTime($this->startTime->format('H.i.s'));
        $endTime = new \DateTime($this->endTime->format('H.i.s'));
        $signInTime = new \DateTime($signInDateTime->format('H.i.s'));

        if ($signInTime < $startTime || $signInTime > $endTime) {
            throw new \InvalidArgumentException(sprintf('Time %s is outside the bounds of the shift (%s to %s)',
                $signInTime->format('H:i:s'),
                $startTime->format('H:i:s'),
                $endTime->format('H:i:s')));
        }

        $tardinessAmount = $startTime->diff($signInTime);

        // We consider hours here in case the mentor is over an hour late, which technically could happen
        return ($tardinessAmount->h * 60) + $tardinessAmount->i;
    }
}
