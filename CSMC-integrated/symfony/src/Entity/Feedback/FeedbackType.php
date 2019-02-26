<?php

namespace App\Entity\Feedback;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="feedback_type")
 */
class FeedbackType {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="name", length=16)
     */
    private $name;

    /**
     * @ORM\Column(type="string", name="action", length=16)
     */
    private $action;

    /**
     * @ORM\Column(type="integer", name="priority")
     */
    private $priority;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return FeedbackType
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set action
     *
     * @param string $action
     *
     * @return FeedbackType
     */
    public function setAction($action) {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     *
     * @return FeedbackType
     */
    public function setPriority($priority) {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority
     *
     * @return integer
     */
    public function getPriority() {
        return $this->priority;
    }
}
