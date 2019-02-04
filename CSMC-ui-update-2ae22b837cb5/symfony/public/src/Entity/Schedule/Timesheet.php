<?php

namespace App\Entity\Schedule;

use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Schedule\TimesheetRepository")
 * @ORM\Table(name="timesheet")
 */
class Timesheet {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\Column(type="datetime", name="time_in", nullable=true)
     */
    private $timeIn;

    /**
     * @ORM\Column(type="datetime", name="time_out", nullable=true)
     */
    private $timeOut;

    /**
     * Constructor
     */
    public function __construct(User $user) {
        $this->user = $user;
        $this->timeIn = new \DateTime();
    }

    public function signOut() {
        $this->timeOut = new \DateTime();
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
     * Get timeIn
     *
     * @return \DateTime
     */
    public function getTimeIn() {
        return $this->timeIn;
    }

    /**
     * Get timeOut
     *
     * @return \DateTime
     */
    public function getTimeOut() {
        return $this->timeOut;
    }

    /**
     * Get user
     *
     * @return \App\Entity\User\User
     */
    public function getUser() {
        return $this->user;
    }

}
