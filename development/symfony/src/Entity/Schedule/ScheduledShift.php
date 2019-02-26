<?php

namespace App\Entity\Schedule;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Schedule\ScheduledShiftRepository")
 * @ORM\Table(name="scheduled_shift")
 */
class ScheduledShift {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="date", name="date_scheduled")
     */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity="Shift")
     * @ORM\JoinColumn(name="shift_id", referencedColumnName="id")
     */
    private $shift;

    /**
     * @ORM\OneToMany(targetEntity="ShiftAssignment", mappedBy="scheduledShift", cascade={"persist", "remove"})
     */
    private $assignments;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Schedule\Schedule", inversedBy="scheduledShifts")
     */
    private $schedule;

    /**
     * Constructor
     */
    public function __construct(Schedule $schedule, Shift $shift, \DateTime $date) {
        $this->schedule = $schedule;
        $this->shift = $shift;
        $this->date = $date;

        $this->assignments = new \Doctrine\Common\Collections\ArrayCollection();

        $this->updateAssignments();
    }

    public function updateAssignments() {
        // get shift's mentors into a flat array with the subject
        $mentors = array();
        foreach ($this->shift->getSubjects() as $subject) {
            foreach ($subject->getMentors() as $mentor) {
                $mentors[] = array(
                    'subject' => $subject->getSubject(),
                    'mentor' => $mentor,
                    'assigned' => false
                );
            }
        }

        if($this->shift->getShiftLeader() != null) {
            $mentors[] = array(
                'subject' => null,
                'mentor' => $this->shift->getShiftLeader(),
                'assigned' => false
            );
        }

        // check if assignments still exist in the shift's mentors and update subject
        foreach ($this->assignments as $assignment) {
            $found = false;
            foreach ($mentors as $mentor) {
                if ($assignment->getMentor() == $mentors['mentor']) {
                    $assignment->updateSubject($mentors['subject']);

                    $found = true;
                    $mentor['assigned'] = true;
                }
            }

            if(!$found) {
                $this->removeAssignment($assignment);
            }
        }

        // create new assignments for mentors not currently assigned
        foreach($mentors as $mentor) {
            if($mentor['assigned']) {
                continue;
            }

            $assignment = new ShiftAssignment($this, $mentor['subject'], $mentor['mentor']);

            $this->assignments[] = $assignment;

            // unneeded but why not
            $mentor['assigned'] = true;
        }
    }

    /**
     * Get id
     *
     * @return guid
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return ScheduledShift
     */
    public function setDate($date) {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Set shift
     *
     * @param \App\Entity\Schedule\Shift $shift
     *
     * @return ScheduledShift
     */
    public function setShift(\App\Entity\Schedule\Shift $shift = null) {
        $this->shift = $shift;

        return $this;
    }

    /**
     * Get shift
     *
     * @return \App\Entity\Schedule\Shift
     */
    public function getShift() {
        return $this->shift;
    }

    /**
     * Get assignments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAssignments() {
        return $this->assignments;
    }

    /**
     * Set schedule
     *
     * @param \App\Entity\Schedule\Schedule $schedule
     *
     * @return ScheduledShift
     */
    public function setSchedule(\App\Entity\Schedule\Schedule $schedule = null) {
        $this->schedule = $schedule;

        return $this;
    }

    /**
     * Get schedule
     *
     * @return \App\Entity\Schedule\Schedule
     */
    public function getSchedule() {
        return $this->schedule;
    }

    /**
     * Get shift leader
     *
     * @return \App\Entity\User\User
     */
    public function getShiftLeader() {
        return $this->shift->getShiftLeader();
    }
}
