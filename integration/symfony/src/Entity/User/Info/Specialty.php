<?php

namespace App\Entity\User\Info;

use App\Entity\Misc\Subject;
use App\Form\Data\SpecialtyFormData;
use Doctrine\ORM\Mapping as Orm;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_specialty",  uniqueConstraints={
 *     @ORM\UniqueConstraint(name="UQ_specialty_user_topic", columns={"profile_id", "specialty_topic_id"})
 * }))
 */
class Specialty
{
    const DEFAULT_SUBJECT_RATING = 1;
    /**
     * @ORM\Id()
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Profile", inversedBy="specialties")
     * @ORM\JoinColumn(name="profile_id", referencedColumnName="user_id")
     */
    private $profile;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Misc\Subject")
     * @ORM\JoinColumn(name="specialty_topic_id", referencedColumnName="id")
     */
    private $topic;

    /**
     * @ORM\Column(type="integer", name="rating", length=1)
     */
    private $rating;

    public function __construct(Profile $profile, Subject $subject, int $rating = Specialty::DEFAULT_SUBJECT_RATING)
    {
        $this->profile = $profile;
        $this->topic = $subject;
        $this->rating = $rating;
    }

    public static function createFromFormData(SpecialtyFormData $formData)
    {
        return new Specialty($formData->getProfile(), $formData->getTopic(), $formData->getRating());
    }

    public function updateFromFormData(SpecialtyFormData $formData)
    {
        $this->rating = $formData->getRating();
    }

    /**
     * Get id
     *
     * @return String
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get rating
     *
     * @return integer
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param mixed $rating
     */
    public function setRating($rating)
    {
        $this->rating = $rating;
    }

    /**
     * Get topic
     *
     * @return \App\Entity\Misc\Subject
     */
    public function getSubject()
    {
        return $this->topic;
    }

    /**
     * @return mixed
     */
    public function getProfile()
    {
        return $this->profile;
    }
}
