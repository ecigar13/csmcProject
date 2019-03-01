<?php


namespace App\Entity\User\Info;


use Doctrine\ORM\Mapping as ORM;

/**
 * @package App\Entity\User\Info
 * @ORM\Entity
 */
class PreferredNameModificationRequest extends ProfileModificationRequest
{
    /**
     * Mirrors @see Profile::$preferredName
     *
     * @ORM\Column(type="string", length=17, nullable=false)
     * @var string
     */
    private $newPreferredName;

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
        $this->newPreferredName = $value;
    }

    /**
     * Returns the value corresponding to this request.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->newPreferredName;
    }
}