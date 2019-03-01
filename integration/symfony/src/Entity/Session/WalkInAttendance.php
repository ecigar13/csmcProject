<?php

namespace App\Entity\Session;

use App\Entity\Course\Course;
use App\Entity\Course\Section;
use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Session\WalkInAttendanceRepository")
 * @ORM\Table(name="walkin_attendance")
 */
class WalkInAttendance extends Attendance {
    /**
     * @ORM\ManyToOne(targetEntity="WalkInActivity")
     * @ORM\JoinColumn(name="activity_id", referencedColumnName="id")
     */
    private $activity;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Course\Course")
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id")
     */
    private $course;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Course\Section")
     * @ORM\JoinColumn(name="section_id", referencedColumnName="id")
     */
    private $section;

    /**
     * @ORM\Column(type="string", name="topic", length=32)
     */
    private $topic;

    /**
     * @ORM\Column(type="string", name="feedback", length=128, nullable=true)
     */
    private $feedback;

    public function __construct(User $user, WalkInActivity $activity, string $topic, Course $course, Section $section = null, \DateTime $dateTime = null) {
        parent::__construct($user, $dateTime);

        $this->activity = $activity;
        $this->topic = $topic;
        $this->course = $course;
        $this->section = $section;
    }

    public function checkOut(array $mentors, \DateTime $dateTime = null, string $feedback = null) {
        parent::checkOut($mentors, $dateTime);

        $this->feedback = $feedback;
    }

    /**
     * Set topic
     *
     * @param string $topic
     *
     * @return WalkInAttendance
     */
    public function setTopic($topic) {
        $this->topic = $topic;

        return $this;
    }

    /**
     * Get topic
     *
     * @return string
     */
    public function getTopic() {
        return $this->topic;
    }

    /**
     * Set feedback
     *
     * @param string $feedback
     *
     * @return WalkInAttendance
     */
    public function setFeedback($feedback) {
        $this->feedback = $feedback;

        return $this;
    }

    /**
     * Get feedback
     *
     * @return string
     */
    public function getFeedback() {
        return $this->feedback;
    }

    /**
     * Set activity
     *
     * @param \App\Entity\Session\WalkInActivity $activity
     *
     * @return WalkInAttendance
     */
    public function setActivity(\App\Entity\Session\WalkInActivity $activity = null) {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return \App\Entity\Session\WalkInActivity
     */
    public function getActivity() {
        return $this->activity;
    }

    /**
     * Set section
     *
     * @param \App\Entity\Course\Section $section
     *
     * @return WalkInAttendance
     */
    public function setSection(\App\Entity\Course\Section $section = null) {
        $this->section = $section;

        return $this;
    }

    /**
     * Get section
     *
     * @return \App\Entity\Course\Section
     */
    public function getSection() {
        return $this->section;
    }

    /**
     * Set course
     *
     * @param \App\Entity\Course\Course $course
     *
     * @return WalkInAttendance
     */
    public function setCourse(\App\Entity\Course\Course $course = null) {
        $this->course = $course;

        return $this;
    }

    /**
     * Get course
     *
     * @return \App\Entity\Course\Course
     */
    public function getCourse() {
        return $this->course;
    }
}
