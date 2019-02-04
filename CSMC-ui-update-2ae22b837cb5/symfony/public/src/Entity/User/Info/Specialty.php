<?php

namespace App\Entity\User\Info;

use App\Entity\Misc\Subject;
use Doctrine\ORM\Mapping as Orm;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_specialty",  uniqueConstraints={
 *     @ORM\UniqueConstraint(name="UQ_specialty_user_topic", columns={"info_id", "specialty_topic_id"})
 * }))
 */
class Specialty {
    /**
     * @ORM\Id()
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Info", inversedBy="specialties")
     * @ORM\JoinColumn(name="info_id", referencedColumnName="user_id")
     */
    private $info;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Misc\Subject")
     * @ORM\JoinColumn(name="specialty_topic_id", referencedColumnName="id")
     */
    private $topic;

    /**
     * @ORM\Column(type="integer", name="rating", length=1)
     */
    private $rating;

    public function __construct(Info $info, Subject $subject, int $rating) {
        $this->info = $info;
        $this->topic = $subject;
        $this->rating = $rating;
    }

    public function updateRating(int $rating) {
        $this->rating = $rating;

        return $this;
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
     * Get rating
     *
     * @return integer
     */
    public function getRating() {
        return $this->rating;
    }

    /**
     * Get topic
     *
     * @return \App\Entity\Misc\Subject
     */
    public function getSubject() {
        return $this->topic;
    }
}
