<?php


namespace App\Form\Data;


use App\Entity\Misc\Subject;
use App\Entity\User\Info\Profile;
use App\Entity\User\Info\Specialty;
use Symfony\Component\Validator\Constraints as Assert;

class SpecialtyFormData
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Profile
     */
    private $profile;

    /**
     * @var Subject
     */
    private $topic;

    /**
     * @Assert\Range(
     *     min = 1,
     *     max = 5,
     *     minMessage = "Rating should be {{ limit }} or more",
     *     maxMessage = "Rating should be {{ limit }} or less"
     * )
     *
     * @var integer
     */
    private $rating;

    public static function createFromSpecialty(Specialty $specialty)
    {
        $formData = new self();

        $formData->id = $specialty->getId();
        $formData->profile = $specialty->getProfile();
        $formData->topic = $specialty->getSubject();
        $formData->rating = $specialty->getRating();

        return $formData;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @param Profile $profile
     */
    public function setProfile(Profile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * @return Subject
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @param Subject $topic
     */
    public function setTopic(Subject $topic)
    {
        $this->topic = $topic;
    }

    /**
     * @return int
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param int $rating
     */
    public function setRating(int $rating)
    {
        $this->rating = $rating;
    }
}