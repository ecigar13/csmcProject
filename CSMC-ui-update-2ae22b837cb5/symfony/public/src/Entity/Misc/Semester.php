<?php

namespace App\Entity\Misc;

use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Misc\SemesterRepository")
 * @ORM\Table(name="semester", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="UQ_semester_season_year", columns={"season", "year"})
 *     })
 * @ORM\HasLifecycleCallbacks
 *
 *
 * @UniqueEntity(
 *     fields={"season", "year"},
 *     message="Semester already exists!"
 * )
 */
class Semester {
    const SEASON_FALL = 'fall';
    const SEASON_SPRING = 'spring';
    const SEASON_SUMMER = 'summer';
    const SEASON_DEV = 'dev';

    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=8, name="season")
     */
    private $season;

    /**
     * @ORM\Column(type="integer", name="year")
     */
    private $year;

    /**
     * @ORM\Column(type="date", name="start_date", nullable=true)
     */
    private $startDate;

    /**
     * @ORM\Column(type="date", name="end_date", nullable=true)
     */
    private $endDate;

    /**
     * @ORM\Column(type="boolean", name="active")
     *
     */
    private $active;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Schedule\Schedule", mappedBy="semester")
     */
    private $schedule;

    /**
     * Semester constructor.
     *
     * @param $season
     * @param $year
     * @param $startDate
     * @param $endDate
     * @param $active
     */
    public function __construct(string $season, int $year, \DateTime $startDate, \DateTime $endDate, bool $active) {
        $this->season = $season;
        $this->year = $year;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->active = $active;
    }


    /**
     * @ORM\PreFlush
     */
    public function preFlush(PreFlushEventArgs $args) {
        if ($this->active) {
            $old = $args->getEntityManager()
                ->getRepository(Semester::class)
                ->findActive();

            if ($old and $old != $this) {
                $old->setActive(false);
            }
        }
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get abbreviation
     *
     * @return string
     */
    public function getAbbreviation() {
        return $this->season->getPrefix() . substr(((string)$this->year), -2);
    }

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return Semester
     */
    public function setActive($active) {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive() {
        return $this->active;
    }

    /**
     * Set season
     *
     * @param string $season
     *
     * @return Semester
     */
    public function setSeason($season) {
        $this->season = $season;

        return $this;
    }

    /**
     * Get season
     *
     * @return string
     */
    public function getSeason() {
        return $this->season;
    }

    /**
     * Set year
     *
     * @param integer $year
     *
     * @return Semester
     */
    public function setYear($year) {
        $this->year = $year;

        return $this;
    }

    /**
     * Get year
     *
     * @return integer
     */
    public function getYear() {
        return $this->year;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return Semester
     */
    public function setStartDate($startDate) {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate() {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return Semester
     */
    public function setEndDate($endDate) {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate() {
        return $this->endDate;
    }

    /**
     * Set schedule
     *
     * @param \App\Entity\Schedule\Schedule $schedule
     *
     * @return Semester
     */
    public function setSchedule(\App\Entity\Schedule\Schedule $schedule = null)
    {
        $this->schedule = $schedule;

        return $this;
    }

    /**
     * Get schedule
     *
     * @return \App\Entity\Schedule\Schedule
     */
    public function getSchedule()
    {
        return $this->schedule;
    }
}
