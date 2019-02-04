<?php

namespace App\Entity\Session;

use App\Entity\Misc\Room;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Session\QuizRepository")
 * @ORM\Table(name="quiz")
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Quiz extends Session {
    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Session\QuizTimeSlot", mappedBy="quiz", cascade={"persist", "remove"})
     */
    private $timeSlot;

    public function __construct(SessionType $type, string $topic, Room $location, \DateTime $start, \DateTime $end, string $description = null, string $studentInstructions = null, string $mentorInstructions = null, bool $graded = true, bool $numericGrade = true) {
        parent::__construct($type, $topic, $description, $studentInstructions, $mentorInstructions, $graded, $numericGrade);

        $this->timeSlot = new QuizTimeSlot($this, $location, $start, $end);
    }

    public static function createFromFormData(array $data) {
        $session = new Quiz($data['type'], $data['topic'], $data['room'], $data['startDate'], $data['endDate'], $data['description'], $data['studentInstructions'],
            $data['mentorInstructions'], $data['graded'], $data['numericGrade']);

        // todo attach files

        return $session;
    }

    public function getTimeSlot() {
        return $this->timeSlot;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate() {
        return $this->timeSlot->getStartTime();
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate() {
        return $this->timeSlot->getEndTime();
    }

    /**
     * Get location
     *
     * @return String
     */
    public function getLocation() {
        return $this->timeSlot->getLocation();
    }
}
