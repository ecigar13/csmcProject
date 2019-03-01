<?php

namespace App\Entity\Misc;

use App\Entity\Interfaces\ModifiableInterface;
use App\Entity\Traits\ModifiableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="holidays")
 */
class Holidays implements ModifiableInterface {
    use ModifiableTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="date", name="date")
     */
    private $date;
    // TODO remove and replace with just the start and end times

    /**
     * @ORM\Column(type="time", name="start_time")
     */
    private $startTime;

    /**
     * @ORM\Column(type="time", name="end_tme")
     */
    private $endTime;

    /**
     * @ORM\Column(type="string", name="day")
     */
    private $day;
    // TODO is this necessary?

    /**
     * @ORM\Column(type="boolean", name="closed")
     */
    private $closed;

    /**
     * @ORM\Column(type="string", name="description", length=8192)
     *
     * @Assert\Length(
     *      max = 8192,
     *      maxMessage = "Description cannot be longer than {{ limit }} characters"
     * )
     */
    private $description;

    /**
     * Returns the holiday's id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the day for the holiday
     *
     * @return string
     */
    public function getDay() {
        return $this->day;
    }

    /**
     * Sets the day for the holiday
     *
     * @param string $day
     *
     * @return Holidays
     */
    public function setDay($day) {
        $this->day = $day;
    }


    /**
     * Returns the date for the holiday
     *
     * @return date
     */
    public function getHolidayDate() {
        return $this->holidayDate;
    }

    /**
     * Sets the date for the holiday
     *
     * @param string $holidayDate
     *
     * @return Holidays
     */
    public function setHolidayDate($holidayDate) {
        $this->holidayDate = $holidayDate;
    }

    /**
     * Returns the start time for the holiday
     *
     * @return datetime
     */
    public function getStartTime() {
        return $this->startTime;
    }

    /**
     * Sets the start time for the holiday
     *
     * @param string $startTime
     *
     * @return Holidays
     */
    public function setStartTime($startTime) {
        $this->startTime = $startTime;
    }


    /**
     * Returns the end time for the holiday
     *
     * @return datetime
     */
    public function getEndTime() {
        return $this->endTime;
    }

    /**
     * Sets the end time for the holiday
     *
     * @param string $endTime
     *
     * @return Holidays
     */
    public function setEndTime($endTime) {
        $this->endTime = $endTime;
    }

    /**
     * Set closed
     *
     * @param boolean $closed
     *
     * @return Holidays
     */
    public function setClosed($closed) {
        $this->closed = $closed;

        return $this;
    }

    /**
     * Get closed
     *
     * @return boolean
     */
    public function getClosed() {
        return $this->closed;
    }


    /**
     * Returns the description for the holiday
     *
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Sets the description for the holiday
     *
     * @param string $description
     *
     * @return Holidays
     */
    public function setDescription($description) {
        $this->description = $description;
    }
}

