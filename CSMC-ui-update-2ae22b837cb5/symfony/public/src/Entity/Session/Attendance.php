<?php

namespace App\Entity\Session;

use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Session\AttendanceRepository")
 * @ORM\Table(name="attendance")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"attendance" = "Attendance", 
 *      "session" = "SessionAttendance",
 *      "scheduled" = "ScheduledSessionAttendance",
 *      "quiz" = "QuizAttendance",
 *      "walkin" = "WalkInAttendance"})
 */
abstract class Attendance {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\Column(type="datetime", name="time_in", nullable=true)
     */
    private $timeIn;

    /**
     * @ORM\Column(type="datetime", name="time_out", nullable=true)
     */
    private $timeOut;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Entity\User\User")
     * @ORM\JoinTable(name="attendance_mentors",
     *      joinColumns={@ORM\JoinColumn(name="attendance_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     *      )
     */
    private $mentors;

    /**
     * Constructor
     */
    public function __construct(User $user, \DateTime $dateTime = null) {
        $this->user = $user;

        $this->timeIn = $dateTime ?? new \DateTime();

        $this->mentors = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set timeIn
     *
     * @param \DateTime $timeIn
     *
     * @return Attendance
     */
    public function setTimeIn($timeIn) {
        $this->timeIn = $timeIn;

        return $this;
    }

    /**
     * Get timeIn
     *
     * @return \DateTime
     */
    public function getTimeIn() {
        return $this->timeIn;
    }

    /**
     * Set timeOut
     *
     * @param \DateTime $timeOut
     *
     * @return Attendance
     */
    public function setTimeOut($timeOut) {
        $this->timeOut = $timeOut;

        return $this;
    }

    /**
     * Get timeOut
     *
     * @return \DateTime
     */
    public function getTimeOut() {
        return $this->timeOut;
    }

    /**
     * Set user
     *
     * @param \App\Entity\User\User $user
     *
     * @return Attendance
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
     * Add mentor
     *
     * @param \App\Entity\User\User $mentor
     *
     * @return Attendance
     */
    public function addMentor(\App\Entity\User\User $mentor) {
        $this->mentors[] = $mentor;

        return $this;
    }

    /**
     * Remove mentor
     *
     * @param \App\Entity\User\User $mentor
     */
    public function removeMentor(\App\Entity\User\User $mentor) {
        $this->mentors->removeElement($mentor);
    }

    /**
     * Get mentors
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMentors() {
        return $this->mentors;
    }

    public function checkOut(array $mentors, \DateTime $dateTime = null) {
        if ($this->timeOut == null) {
            foreach ($mentors as $mentor) {
                $this->mentors->add($mentor);
            }
        }

        $this->timeOut = $dateTime ?? new \DateTime();

        return $this;
    }
}
