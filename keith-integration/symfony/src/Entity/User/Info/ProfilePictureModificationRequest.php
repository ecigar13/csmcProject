<?php


namespace App\Entity\User\Info;


use App\Entity\File\File;
use Doctrine\ORM\Mapping as ORM;

/**
 * @package App\Entity\User\Info
 * @ORM\Entity
 */
class ProfilePictureModificationRequest extends ProfileModificationRequest
{
    /**
     * Mirrors @see Profile::$image
     *
     * @ORM\OneToOne(targetEntity="App\Entity\File\File")
     * @var File
     */
    private $newImage;

    /**
     * @param Profile $profile
     */
    public function __construct(Profile $profile)
    {
        parent::__construct($profile);
    }

    /**
     * @inheritdoc
     */
    public function update($value)
    {
        $this->newImage = $value;
    }

    /**
     * Returns the value corresponding to this request.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->newImage;
    }
}