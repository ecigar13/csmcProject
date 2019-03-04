<?php

namespace App\Entity\Misc;

use App\Entity\Interfaces\ModifiableInterface;
use App\Entity\Traits\ModifiableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="operation_hours")
 */
class OperationHours implements ModifiableInterface {
    use ModifiableTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     *
     * @ORM\Column(type="integer", name="day")
     */
    private $day;

    /**
     * @ORM\Column(type="time", name="start_time")
     */
    private $startTime;

    /**
     * @ORM\Column(type="time", name="end_time")
     */
    private $endTime;

    public function __construct(int $day, \DateTime $start, \DateTime $end) {
        $this->day = $day;
        $this->startTime = $start;
        $this->endTime = $end;
    }

    public function getDayName() {
        return date('l', strtotime('Sunday + ' . $this->day . ' days'));
    }

    /**
     * Returns the operation hour's id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the day of operation
     *
     * @return string
     */
    public function getDay() {
        return $this->day;
    }

    /**
     * Sets the day of operation
     *
     * @param string $day
     *
     * @return OperationHours
     */
    public function setDay($day) {
        $this->day = $day;

        return $this;
    }

    /**
     * Returns the start time for the day of operation
     *
     * @return time
     */
    public function getStartTime() {
        return $this->startTime;
    }

    /**
     * Sets the start time for the day of operation
     *
     * @param string $startTime
     *
     * @return OperationHours
     */
    public function setStartTime($startTime) {
        $this->startTime = $startTime;
    }


    /**
     * Returns the end time for the day of operation
     *
     * @return time
     */
    public function getEndTime() {
        return $this->endTime;
    }

    /**
     * Sets the end time for the day of operation
     *
     * @param string $endTime
     *
     * @return OperationHours
     */
    public function setEndTime($endTime) {
        $this->endTime = $endTime;
    }
}

