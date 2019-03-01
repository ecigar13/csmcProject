<?php


namespace App\Entity\Traits;

use App\Annotation\Timestampable;

/**
 * @Timestampable()
 */
trait TimestampableTrait {
    /**
     * @ORM\Column(type="datetime", name="updated", nullable=true)
     */
    private $updated;

    /**
     * @ORM\Column(type="datetime", name="created", nullable=true)
     */
    private $created;

    public function getCreated() {
        return $this->created;
    }

    public function getUpdated() {
        return $this->updated;
    }
}