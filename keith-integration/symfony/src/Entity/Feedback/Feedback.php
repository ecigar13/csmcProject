<?php

namespace App\Entity\Feedback;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="feedback")
 */
class Feedback {
    // TODO redo this whole assortment of entities
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *
     */
    private $user;

    /**
     * @ORM\Column(type="string", name="message", length=1024)
     */
    private $message;

    /**
     * @ORM\Column(type="datetime", name="post_date")
     */
    private $postDate;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Feedback\FeedbackType")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     */
    private $type;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set message
     *
     * @param string $message
     *
     * @return Feedback
     */
    public function setMessage($message) {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Set postDate
     *
     * @param \DateTime $postDate
     *
     * @return Feedback
     */
    public function setPostDate($postDate) {
        $this->postDate = $postDate;

        return $this;
    }

    /**
     * Get postDate
     *
     * @return \DateTime
     */
    public function getPostDate() {
        return $this->postDate;
    }

    /**
     * Set user
     *
     * @param \App\Entity\User\User $user
     *
     * @return Feedback
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

    /**
     * Set type
     *
     * @param \App\Entity\Feedback\FeedbackType $type
     *
     * @return Feedback
     */
    public function setType(\App\Entity\Feedback\FeedbackType $type = null) {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \App\Entity\Feedback\FeedbackType
     */
    public function getType() {
        return $this->type;
    }
}
