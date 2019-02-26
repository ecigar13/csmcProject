<?php

namespace App\Entity\Session;

use App\Entity\Misc\Room;
use App\Entity\Schedule\Shift;
use App\Entity\Schedule\ShiftAssignment;
use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Session\ScheduledSession;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Session\SessionTimeSlotRepository")
 * @ORM\Table(name="session_timeslot")
 */
class SessionTimeSlot extends TimeSlot {
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Session\ScheduledSession", inversedBy="timeSlots")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    private $session;

    /**
     * @ORM\OneToMany(targetEntity="Registration", mappedBy="timeSlot", cascade={"persist", "remove"})
     */
    private $registrations;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\User\User")
     * @ORM\JoinColumn(name="sub_user_id", referencedColumnName="id")
     */
    private $subMentor;

    /**
     * @ORM\Column(type="integer", name="capacity")
     */
    private $capacity;

    /**
     * @ORM\OneToMany(targetEntity="\App\Entity\Schedule\ShiftAssignment", mappedBy="session", cascade={"persist", "detach"})
     */
    private $assignments;

    /**
     * @ORM\Column(type="time", name="actual_start_time", nullable=true)
     */
    private $actualStartTime;

    /**
     * @ORM\Column(type="time", name="actual_end_time", nullable=true)
     */
    private $actualEndTime;

    public function __construct(ScheduledSession $session, Room $location, \DateTime $start, \DateTime $end, int $capacity) {
        parent::__construct($location, $start, $end);

        $this->session = $session;
        $this->capacity = $capacity;

        $this->assignments = new ArrayCollection();
        $this->registrations = new ArrayCollection();
    }

    public static function createFromFormData(array $data) {
        $ts = new SessionTimeSlot($data['session'], $data['location'], $data['start'], $data['end'], $data['capacity']);

        return $ts;
    }

    public function updateCapacity(int $capacity) {
        $this->capacity = $capacity;
    }

    public function getName() {
        return $this->session->getTopic();
    }

    public function getSession() {
        return $this->session;
    }

    public function getCapacity() {
        return $this->capacity;
    }

    public function register(User $user) {
        if ($this->isRegistered($user) || $this->getRemainingSeats() <= 0) {
            return false;
        }

        $this->registrations[] = new Registration($this, $user);

        return true;
    }

    public function isRegistered(User $user) {
        foreach ($this->registrations as $registration) {
            if ($registration->getUser()->getId() == $user->getId()) {
                return true;
            }
        }
        return false;
    }

    public function assign(ShiftAssignment $assignment) {
        $this->assignments[] = $assignment;

        $assignment->assignToSession($this);
    }

    public function unassign(User $mentor) {
        foreach($this->assignments as $assignment) {
            if($assignment->getMentor() == $mentor) {
                $this->assignments->removeElement($assignment);

                // Breaking OOP principles with reflection for the fun of it
                $class = new \ReflectionClass(ShiftAssignment::class);
                $prop = $class->getProperty('session');
                $prop->setAccessible(true);
                $prop->setValue($assignment, null);

                return;
            }
        }
    }

    public function getMentors() {
        $mentors = new ArrayCollection();
        foreach ($this->assignments as $assignment) {
            $mentors[] = $assignment->getMentor();
        }

        return $mentors;
    }

    public function getAssignments() {
        return $this->assignments;
    }

    public function getRegisteredStudents() {
        $students = new ArrayCollection();
        foreach ($this->registrations as $registration) {
            $students[] = $registration->getUser();
        }

        $iterator = $students->getIterator();
        $iterator->uasort(function ($first, $second) {
            return $first->getFirstName() > $second->getFirstName() ? 1 : -1;
        });


        return new ArrayCollection(iterator_to_array($iterator));
    }

    /**
     * @return mixed
     */
    public function getRemainingSeats() {
        return $this->capacity - (($this->registrations->count() > $this->getAttendances()
                    ->count()) ? $this->registrations->count() : $this->getAttendances()->count());
    }

    public function start(User $user, \DateTime $startTime = null) {
        $this->actualStartTime = $startTime ?? new \DateTime();

        return $this;
    }

    public function end(User $user, \DateTime $endTime = null) {
        $this->actualEndTime = $endTime ?? new \DateTime();

        return $this;
    }

    public function hasStarted() {
        return $this->actualStartTime != null;
    }

    public function hasEnded() {
        return $this->actualEndTime != null;
    }

    public function checkIn(User $user, \DateTime $dateTime = null) {
        $attendance = new ScheduledSessionAttendance($user, $this, $dateTime);
        $this->attendances[] = $attendance;

        return $attendance;
    }

    public function getColor() {
        return $this->getColor() ?? $this->session->getColor();
    }

    public function eligible(User $user) {
        $students = $this->session->getStudents();

        foreach($students as $student) {
            if($user->getUsername() == $user->getUsername()) {
                return true;
            }
        }

        return false;
    }
}