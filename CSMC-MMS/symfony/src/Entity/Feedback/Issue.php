<?php

namespace App\Entity\Feedback;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="issue")
 */
class Issue {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", name="open_date")
     */
    private $openDate;

    /**
     * @ORM\OneToOne(targetEntity="Feedback")
     * @ORM\JoinColumn(name="feedback_id", referencedColumnName="id")
     */
    private $feedback;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\Column(type="boolean", name="resolved")
     */
    private $resolved;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set openDate
     *
     * @param \DateTime $openDate
     *
     * @return Issue
     */
    public function setOpenDate($openDate) {
        $this->openDate = $openDate;

        return $this;
    }

    /**
     * Get openDate
     *
     * @return \DateTime
     */
    public function getOpenDate() {
        return $this->openDate;
    }

    /**
     * Set resolved
     *
     * @param boolean $resolved
     *
     * @return Issue
     */
    public function setResolved($resolved) {
        $this->resolved = $resolved;

        return $this;
    }

    /**
     * Get resolved
     *
     * @return boolean
     */
    public function getResolved() {
        return $this->resolved;
    }

    /**
     * Set feedback
     *
     * @param \App\Entity\Feedback\Feedback $feedback
     *
     * @return Issue
     */
    public function setFeedback(\App\Entity\Feedback\Feedback $feedback = null) {
        $this->feedback = $feedback;

        return $this;
    }

    /**
     * Get feedback
     *
     * @return \App\Entity\Feedback\Feedback
     */
    public function getFeedback() {
        return $this->feedback;
    }

    /**
     * Set user
     *
     * @param \App\Entity\User\User $user
     *
     * @return Issue
     */
    public function setUser(\App\Entity\User\User $user = null) {
        $this->user = $user;

        return $this;
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
