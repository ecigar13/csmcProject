<?php

namespace App\Entity\Schedule;

use App\Entity\Misc\Subject;
use App\Entity\Occurrence\AbsenceOccurrence;
use App\Entity\Session\SessionTimeSlot;
use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;

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

    /**
     * Stores the date when this assignment was added to a session. Used to send session assignment notifications.
     *
     * @ORM\Column(type="date", nullable=true)
     *
     * @var \DateTime
     */
    private $assignedOn;
    public function __construct(ScheduledShift $scheduledShift, Subject $subject = null, User $mentor) {
        $this->scheduledShift = $scheduledShift;
        $this->subject = $subject;
        $this->mentor = $mentor;
    }

    public function updateSubject(Subject $subject = null) {
        $this->subject = $subject;

        return $this;
    }

    public function assignToSession(SessionTimeSlot $timeSlot)
    {
        $this->session = $timeSlot;
        $this->assignedOn = new \DateTime();
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

    public function getAbsenceNoticeAmountInHours() {
        $absenceNotice = $this->getAbsence();
        if ($absenceNotice == null) {
            return AbsenceOccurrence::NO_NOTICE;
        } else {
            $noticeTime = $absenceNotice->getCreatedOn();
            $assignmentDateTime = $this->getAssignmentDateTime();
            // Shouldn't ever happen, but just in case
            if ($noticeTime >= $assignmentDateTime) {
                return AbsenceOccurrence::NO_NOTICE;
            }
            $noticeAmount = $assignmentDateTime->diff($noticeTime);
            $hoursNotice = $noticeAmount->days * 24 + $noticeAmount->h;
            return $hoursNotice;
        }
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
    /**
     * Combines Shift date and ShiftAssignment time attributes to get one DateTime
     *
     * @return \DateTime
     */
    public function getAssignmentDateTime()
    {
        $assignmentDate = $this->getScheduledShift()->getDate();
        $assignmentTime = $this->getScheduledShift()->getShift()->getStartTime();
        $assignmentDateTime = new \DateTime($assignmentDate->format('m/d/Y') . ' ' . $assignmentTime->format('H:i:s'));

        return $assignmentDateTime;
    }
}
