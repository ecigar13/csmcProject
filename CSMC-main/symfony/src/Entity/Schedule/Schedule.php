<?php

namespace App\Entity\Schedule;

use App\Entity\Misc\Semester;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="schedule")
 */
class Schedule {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="Shift", mappedBy="schedule")
     */
    private $shifts;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Schedule\ScheduledShift", mappedBy="schedule")
     */
    private $scheduledShifts;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Misc\Semester", inversedBy="schedule")
     * @ORM\JoinColumn(name="semester_id", referencedColumnName="id")
     */
    private $semester;

    /**
     * Constructor
     */
    public function __construct(Semester $semester) {
        $this->semester = $semester;
        $this->scheduledShifts = new ArrayCollection();
        $this->shifts = new ArrayCollection();
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
     * Add shift
     *
     * @param \App\Entity\Schedule\Shift $shift
     *
     * @return Schedule
     */
    public function addShift(\App\Entity\Schedule\Shift $shift) {
        $this->shifts[] = $shift;

        return $this;
    }

    /**
     * Remove shift
     *
     * @param \App\Entity\Schedule\Shift $shift
     */
    public function removeShift(\App\Entity\Schedule\Shift $shift) {
        $this->shifts->removeElement($shift);
    }

    /**
     * Get shifts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getShifts() {
        return $this->shifts;
    }

    /**
     * Set semester
     *
     * @param \App\Entity\Misc\Semester $semester
     *
     * @return Schedule
     */
    public function setSemester(\App\Entity\Misc\Semester $semester = null) {
        $this->semester = $semester;

        return $this;
    }

    /**
     * Get semester
     *
     * @return \App\Entity\Misc\Semester
     */
    public function getSemester() {
        return $this->semester;
    }

    /**
     * Add scheduledShift
     *
     * @param \App\Entity\Schedule\ScheduledShift $scheduledShift
     *
     * @return Schedule
     */
    public function addScheduledShift(\App\Entity\Schedule\ScheduledShift $scheduledShift)
    {
        $this->scheduledShifts[] = $scheduledShift;

        return $this;
    }

    /**
     * Remove scheduledShift
     *
     * @param \App\Entity\Schedule\ScheduledShift $scheduledShift
     */
    public function removeScheduledShift(\App\Entity\Schedule\ScheduledShift $scheduledShift)
    {
        $this->scheduledShifts->removeElement($scheduledShift);
    }

    /**
     * Get scheduledShifts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getScheduledShifts()
    {
        return $this->scheduledShifts;
    }
}
