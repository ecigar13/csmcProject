<?php

namespace App\Entity\Session;

use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="session_attendance")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
abstract class SessionAttendance extends Attendance {
    /**
     * @ORM\ManyToOne(targetEntity="TimeSlot", inversedBy="attendances")
     * @ORM\JoinColumn(name="timeslot_id", referencedColumnName="id")
     */
    private $timeSlot;

    /**
     * @ORM\Column(type="integer", name="grade", nullable=true)
     */
    private $grade;

    /**
     * @ORM\Column(type="string", name="comments", length=128, nullable=true)
     */
    private $comments;

    public function __construct(User $user, TimeSlot $timeSlot, \DateTime $dateTime = null) {
        parent::__construct($user, $dateTime);

        $this->timeSlot = $timeSlot;
    }

    /**
     * Set grade
     *
     * @param integer $grade
     *
     * @return SessionAttendance
     */
    public function setGrade($grade) {
        $this->grade = $grade;

        return $this;
    }

    /**
     * Get grade
     *
     * @return integer
     */
    public function getGrade() {
        return $this->grade;
    }

    /**
     * Set comments
     *
     * @param string $comments
     *
     * @return SessionAttendance
     */
    public function setComments($comments) {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments
     *
     * @return string
     */
    public function getComments() {
        return $this->comments;
    }
}
