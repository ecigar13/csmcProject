<?php

namespace App\Entity\Schedule;

use App\Entity\Misc\Subject;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User\User;
use App\Entity\Session\SessionTimeSlot;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Schedule\ShiftAssignmentRepository")
 * @ORM\Table(name="shift_assignment")
 */
class ShiftAssignment {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Schedule\ScheduledShift", inversedBy="assignments")
     * @ORM\JoinColumn(name="scheduled_shift_id", referencedColumnName="id")
     */
    private $scheduledShift;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Misc\Subject")
     * @ORM\JoinColumn(name="subject_id", referencedColumnName="id")
     */
    private $subject;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $mentor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Session\SessionTimeSlot", inversedBy="assignments", cascade={"persist", "detach"})
     * @ORM\JoinColumn(name="timeslot_id", referencedColumnName="id")
     */
    private $session;

    /**
     * @ORM\OneToOne(targetEntity="Absence", inversedBy="assignment")
     * @ORM\JoinCOlumn(name="absence_id", referencedColumnName="id")
     */
    private $absence;

    public function __construct(ScheduledShift $scheduledShift, Subject $subject = null, User $mentor) {
        $this->scheduledShift = $scheduledShift;
        $this->subject = $subject;
        $this->mentor = $mentor;
    }

    public function updateSubject(Subject $subject = null) {
        $this->subject = $subject;

        return $this;
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
     * Set mentor
     *
     * @param \App\Entity\User\User $mentor
     *
     * @return ShiftAssignment
     */
    public function setMentor(User $mentor = null) {
        $this->mentor = $mentor;

        return $this;
    }

    /**
     * Get mentor
     *
     * @return \App\Entity\User\User
     */
    public function getMentor() {
        return $this->mentor;
    }

    /**
     * Set session
     *
     * @param \App\Entity\Session\SessionTimeSlot $session
     *
     * @return ShiftAssignment
     */
    public function setSession(SessionTimeSlot $session = null) {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session
     *
     * @return \App\Entity\Session\TimeSlot
     */
    public function getSession() {
        return $this->session;
    }

    /**
     * Set absence
     *
     * @param \App\Entity\Schedule\Absence $absence
     *
     * @return ShiftAssignment
     */
    public function setAbsence(Absence $absence = null) {
        $this->absence = $absence;

        return $this;
    }

    /**
     * Get absence
     *
     * @return \App\Entity\Schedule\Absence
     */
    public function getAbsence() {
        return $this->absence;
    }

    /**
     * Set scheduledShift
     *
     * @param \App\Entity\Schedule\ScheduledShift $scheduledShift
     *
     * @return ShiftAssignment
     */
    public function setScheduledShift(\App\Entity\Schedule\ScheduledShift $scheduledShift = null) {
        $this->scheduledShift = $scheduledShift;

        return $this;
    }

    /**
     * Get scheduledShift
     *
     * @return \App\Entity\Schedule\ScheduledShift
     */
    public function getScheduledShift() {
        return $this->scheduledShift;
    }

    /**
     * Set subject
     *
     * @param Subject $subject
     *
     * @return ShiftAssignment
     */
    public function setSubject(Subject $subject = null) {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return Subject
     */
    public function getSubject() {
        return $this->subject;
    }
}
