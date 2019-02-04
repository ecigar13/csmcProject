<?php

namespace App\Entity\Session;

use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Course\Course;
use App\Entity\Course\Section;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Session\QuizAttendanceRepository")
 * @ORM\Table(name="quiz_attendance")
 */
class QuizAttendance extends SessionAttendance {

    /**
     * @ORM\ManyToOne(targetEntity="Quiz", inversedBy="attendances")
     * @ORM\JoinColumn(name="quiz_id", referencedColumnName="id")
     */
    private $quiz;

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
     * @ORM\Column(type="string", name="feedback", length=128, nullable=true)
     */
    private $feedback;

    public function __construct(User $user, QuizTimeSlot $timeSlot, \DateTime $dateTime = null) {
        parent::__construct($user, $timeSlot, $dateTime);

        $this->quiz = $timeSlot->getQuiz();
    }

    /**
     * Set quiz
     *
     * @param \App\Entity\Session\Quiz $quiz
     *
     * @return QuizAttendance
     */
    public function setQuiz(\App\Entity\Session\Quiz $quiz = null) {
        $this->quiz = $quiz;

        return $this;
    }

    /**
     * Get quiz
     *
     * @return \App\Entity\Session\Quiz
     */
    public function getQuiz() {
        return $this->quiz;
    }

    /**
     * Set feedback
     *
     * @param string $feedback
     *
     * @return QuizAttendance
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
     * Set course
     *
     * @param \App\Entity\Course\Course $course
     *
     * @return QuizAttendance
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

    /**
     * Set section
     *
     * @param \App\Entity\Course\Section $section
     *
     * @return QuizAttendance
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
}
