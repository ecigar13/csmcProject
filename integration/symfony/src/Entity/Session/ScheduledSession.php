<?php

namespace App\Entity\Session;

use App\Entity\Misc\Room;
use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Session\ScheduledSessionRepository")
 * @ORM\Table(name="scheduled_session")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
class ScheduledSession extends Session {
    /**
     * @ORM\Column(type="integer", name="repeats")
     */
    private $repeats;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Session\SessionTimeSlot", mappedBy="session", cascade={"persist", "remove"})
     */
    private $timeSlots;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Misc\Room")
     * @ORM\JoinColumn(name="default_room_id", referencedColumnName="id")
     */
    private $defaultLocation;

    /**
     * @ORM\Column(type="integer", name="default_capacity")
     */
    private $defaultCapacity;

    /**
     * @ORM\Column(type="dateinterval", name="default_duration")
     */
    private $defaultDuration;

    public function __construct(SessionType $type, string $topic, int $repeats, string $description = '', string $studentInstructions = '', string $mentorInstructions = '', bool $graded = false, bool $numericGrade = false) {
        parent::__construct($type, $topic, $description, $studentInstructions, $mentorInstructions, $graded, $numericGrade);

        $this->repeats = $repeats;
        $this->timeSlots = new ArrayCollection();
    }

    public static function createFromFormData(array $data) {
        $session = new ScheduledSession($data['type'], $data['topic'], $data['repeats'], $data['description']==null?'':$data['description'], $data['studentInstructions']==null?'':$data['studentInstructions'],
            $data['mentorInstructions']==null?'':$data['mentorInstructions'], $data['graded'], $data['numericGrade']);

        foreach ($data['sections'] as $section) {
            $session->addSection($section);
        }

        $session->setDefaults($data['defaultLocation'], $data['defaultCapacity'], $data['defaultDuration']);

        // todo attach files

        return $session;
    }

    public function setDefaults(Room $location, int $capacity, \DateInterval $duration) {
        $this->defaultLocation = $location;
        $this->defaultCapacity = $capacity;
        $this->defaultDuration = $duration;
    }

    // TODO improve this, probably real inefficient and doesn't work when someone is in more than one section ????
    public function isRegistered($user) {
        foreach ($this->timeSlots as $timeSlot) {
            if($timeSlot->isRegistered($user)) {
                return true;
            }
        }

        return false;
    }

    public function hasAttended($user) {
        foreach ($this->timeSlots as $timeSlot) {
            foreach ($timeSlot->getAttendances() as $attendance) {
                if ($attendance->getUser() == $user) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getAttendance(User $user) {
        foreach ($this->timeSlots as $timeSlot) {
            $a = $timeSlot->getAttendance($user);
            if($a != null) {
                return $a;
            }
        }
        return null;
    }

    /**
     * Add timeSlot
     *
     * @param SessionTimeSlot $timeSlot
     *
     * @return ScheduledSession
     */
    public function addTimeSlot(SessionTimeSlot $timeSlot) {
        $this->timeSlots[] = $timeSlot;

        return $this;
    }

    /**
     * Remove timeSlot
     *
     * @param SessionTimeSlot $timeSlot
     */
    public function removeTimeSlot(SessionTimeSlot $timeSlot) {
        $this->timeSlots->removeElement($timeSlot);
    }

    /**
     * Get timeSlots
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTimeSlots() {
        return $this->timeSlots;
    }

    public function getAttendances() {
        $attendances = array();
        foreach ($this->timeSlots as $timeSlot) {
            $attendances = array_merge($attendances, $timeSlot->getAttendances()->toArray());
        }

        return new ArrayCollection($attendances);
    }

    public function getStartDate() {
        if(!$this->timeSlots->isEmpty()) {
            $start = (new \DateTime($this->timeSlots->first()->getStartTime()->format('m/d/Y')))->setTime(0, 0, 0);
            foreach ($this->timeSlots as $timeSlot) {
                $date = (new \DateTime($timeSlot->getStartTime()->format('m/d/Y')))->SetTime(0, 0, 0);
                if ($date < $start) {
                    $start = $date;
                }
            }

            return $start;
        } else {
            return null;
        }
    }

    public function getEndDate() {
        if(!$this->timeSlots->isEmpty()) {
            $end = (new \DateTime($this->timeSlots->first()->getStartTime()->format('m/d/Y')))->setTime(0, 0, 0);
            foreach ($this->timeSlots as $timeSlot) {
                $date = (new \DateTime($timeSlot->getStartTime()->format('m/d/Y')))->SetTime(0, 0, 0);
                if ($date > $end) {
                    $end = $date;
                }
            }

            return $end;
        } else {
            return null;
        }
    }

    /**
     * Set repeats
     *
     * @param integer $repeats
     *
     * @return ScheduledSession
     */
    public function setRepeats($repeats) {
        $this->repeats = $repeats;

        return $this;
    }

    /**
     * Get repeats
     *
     * @return integer
     */
    public function getRepeats() {
        return $this->repeats;
    }

    public function getDefaultLocation() {
        return $this->defaultLocation;
    }

    public function getDefaultCapacity() {
        return $this->defaultCapacity;
    }

    public function getDefaultDuration() {
        return $this->defaultDuration;
    }
}
