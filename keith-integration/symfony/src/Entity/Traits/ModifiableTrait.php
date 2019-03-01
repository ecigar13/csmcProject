<?php

namespace App\Entity\Traits;

use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;

// TODO change into an annotation (probably 2 annotations, blameable and timestampable)
trait ModifiableTrait {
    /**
     * @ORM\Column(type="datetime", name="last_modified_on")
     */
    private $lastModifiedOn;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User")
     * @ORM\JoinColumn(name="last_modified_by", referencedColumnName="id")
     */
    private $lastModifiedBy;

    /**
     * @ORM\Column(type="datetime", name="created_on")
     */
    private $createdOn;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    private $createdBy;

    /**
     * Get topic
     *
     * @return \DateTime
     */
    public function getLastModifiedOn() {
        return $this->lastModifiedOn;
    }

    /**
     * Set lastModifiedOn
     *
     * @param \DateTime $lastModifiedOn
     *
     * @return ModifiableTrait
     */
    public function setLastModifiedOn() {
        $this->lastModifiedOn = new \DateTime();

        return $this;
    }

    /**
     * Get topic
     *
     * @return User
     */
    public function getLastModifiedBy() {
        return $this->lastModifiedBy;
    }

    /**
     * Set lastModifiedBy
     *
     * @param User $user
     *
     * @return ModifiableTrait
     */
    public function setLastModifiedBy(User $user = null) {
        $this->lastModifiedBy = $user;

        return $this;
    }

    /**
     * Get topic
     *
     * @return \DateTime
     */
    public function getCreatedOn() {
        return $this->createdOn;
    }

    /**
     * Set createdOn
     *
     * @param \DateTime $createdOn
     *
     * @return ModifiableTrait
     */
    public function setCreatedOn() {
        $this->createdOn = new \DateTime();

        return $this;
    }

    /**
     * Get topic
     *
     * @return User
     */
    public function getCreatedBy() {
        return $this->createdBy;
    }

    /**
     * Set createdBy
     *
     * @param User $user
     *
     * @return ModifiableTrait
     */
    public function setCreatedBy(User $user = null) {
        $this->createdBy = $user;

        return $this;
    }
}