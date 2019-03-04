<?php


namespace App\Entity\User\Info;


use Doctrine\ORM\Mapping as ORM;

/**
 * This class contains a unique constraint that ensures there is only one request of each type for each profile.
 *
 * @package App\Entity\User\Info
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="disc", type="string")
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="UQ_profile_mod_request_type", columns={"disc", "profile_id"})
 * })
 */
abstract class ProfileModificationRequest
{
    /**
     * If it was possible to use the discriminator column as part of the primary key we could use the profile ID and the
     * discriminator column, but sadly that is not possible.
     *
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     * @var string
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User\Info\Profile",
     *     inversedBy="modificationRequests",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="profile_id",
     *     referencedColumnName="user_id",
     *     nullable=false
     * )
     * @var Profile
     */
    private $profile;

    /**
     * @param Profile $profile
     */
    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * Updates the profile with the information corresponding to this request.
     */
    public function approve()
    {
        $this->profile->approveModificationRequest($this);
    }

    public function reject()
    {
        $this->profile->rejectModificationRequest($this);
    }

    /**
     * Updates the value corresponding to this request. Used in the case that the user already has a modification request
     * but then changes their profile again.
     *
     * @param $value
     * @return mixed
     */
    public abstract function update($value);

    /**
     * Returns the value corresponding to this request.
     *
     * @return mixed
     */
    public abstract function getValue();

    /**
     * @return Profile
     */
    public function getProfile(): Profile
    {
        return $this->profile;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

}