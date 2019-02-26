<?php

namespace App\Entity\Session;

use App\Entity\Misc\Room;
use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="quiz_timeslot")
 */
class QuizTimeSlot extends TimeSlot {
    /**
     * @ORM\OneToOne(targetEntity="Quiz", inversedBy="timeSlot")
     */
    private $quiz;

    public function __construct(Quiz $quiz, Room $location, \DateTime $start, \DateTime $end) {
        $start->setTime(0, 0, 0);
        $end->add(new \DateInterval('P1D'))->setTime(0, 0, 0); // form is inclusive, calendar is exclusive
        parent::__construct($location, $start, $end);

        $this->quiz = $quiz;
    }

    public function getName() {
        return $this->quiz->getTopic();
    }

    public function checkIn(User $user, \DateTime $dateTime = null) {
        // TODO: Implement checkIn() method.
    }

    public function getQuiz() {
        return $this->quiz;
    }

    public function getColor() {
        return $this->getColor() ?? $this->quiz->getColor();
    }
}