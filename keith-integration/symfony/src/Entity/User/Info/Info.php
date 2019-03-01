<?php

namespace App\Entity\User\Info;

use App\Entity\Misc\Subject;
use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as Orm;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_info")
 */
class Info {
    /**
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="App\Entity\User\User", inversedBy="info")
     */
    private $user;

    /**
     * @ORM\ManyToMany(targetEntity="Major")
     * @ORM\JoinTable(name="user_majors",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="user_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="major_id", referencedColumnName="id")}
     *      )
     */
    private $majors;

    /**
     * @ORM\Column(type="date", name="date_of_birth", nullable=true)
     */
    private $dateOfBirth;

    /**
     * @ORM\Column(type="string", name="phone_number", nullable=true)
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="string", length=254, name="email", nullable=true)
     */
    private $email;

    /**
     * @ORM\ManyToMany(targetEntity="DietaryRestriction")
     * @ORM\JoinTable(name="user_dietary_restrictions",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="user_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="restriction_id", referencedColumnName="id")}
     *     )
     */
    private $dietaryRestrictions;

    /**
     * @ORM\Column(type="text", name="additional_information", nullable=true)
     */
    private $additionalInformation;

    /**
     * @ORM\Column(type="text", name="adminNotes", nullable=true)
     */
    private $adminNotes;

    /**
     * @ORM\OneToMany(targetEntity="Specialty", mappedBy="info", cascade={"persist"})
     */
    private $specialties;

    /**
     * Constructor
     */
    public function __construct(User $user) {
        $this->user = $user;

        $this->specialties = new ArrayCollection();
        $this->dietaryRestrictions = new ArrayCollection();
    }

    public static function createForUser(User $user) {
        $info = new Info($user);
        return $info;
    }

    /**
     * Set dateOfBirth
     *
     * @param \DateTime $dateOfBirth
     *
     * @return Info
     */
    public function setDateOfBirth($dateOfBirth) {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * Get dateOfBirth
     *
     * @return \DateTime
     */
    public function getDateOfBirth() {
        return $this->dateOfBirth;
    }

    /**
     * Set phoneNumber
     *
     * @param string $phoneNumber
     *
     * @return UserInfo
     */
    public function setPhoneNumber($phoneNumber) {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Get phoneNumber
     *
     * @return string
     */
    public function getPhoneNumber() {
        return $this->phoneNumber;
    }

    /**
     * Set user
     *
     * @param \App\Entity\User\User $user
     *
     * @return Info
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
     * Set email
     *
     * @param string $email
     *
     * @return Info
     */
    public function setEmail($email) {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Set additionalInformation
     *
     * @param string $additionalInformation
     *
     * @return Info
     */
    public function setAdditionalInformation($additionalInformation) {
        $this->additionalInformation = $additionalInformation;

        return $this;
    }

    /**
     * Get additionalInformation
     *
     * @return string
     */
    public function getAdditionalInformation() {
        return $this->additionalInformation;
    }

    /**
     * Set adminNotes
     *
     * @param string $adminNotes
     *
     * @return Info
     */
    public function setAdminNotes($adminNotes) {
        $this->adminNotes = $adminNotes;

        return $this;
    }

    /**
     * Get adminNotes
     *
     * @return string
     */
    public function getAdminNotes() {
        return $this->adminNotes;
    }

    /**
     * Get majors
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMajors() {
        return $this->majors;
    }

    /**
     * Set image
     *
     * @param \App\Entity\File\File $image
     *
     * @return Info
     */
    public function setImage(\App\Entity\File\File $image = null) {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return \App\Entity\File\File
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Add dietaryRestriction
     *
     * @param \App\Entity\User\Info\DietaryRestriction $dietaryRestriction
     *
     * @return Info
     */
    public function adddietaryRestriction(\App\Entity\User\Info\DietaryRestriction $dietaryRestriction) {
        $this->dietaryRestrictions[] = $dietaryRestriction;

        return $this;
    }

    /**
     * Remove dietaryRestriction
     *
     * @param \App\Entity\User\Info\DietaryRestriction $dietaryRestriction
     */
    public function removedietaryRestriction(\App\Entity\User\Info\DietaryRestriction $dietaryRestriction) {
        $this->dietaryRestrictions->removeElement($dietaryRestriction);
    }

    /**
     * Get dietaryRestrictions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getdietaryRestrictions() {
        return $this->dietaryRestrictions;
    }

    /**
     * Add major
     *
     * @param \App\Entity\User\Info\Major $major
     *
     * @return Info
     */
    public function addMajor(\App\Entity\User\Info\Major $major) {
        $this->majors[] = $major;

        return $this;
    }

    /**
     * Remove major
     *
     * @param \App\Entity\User\Info\Major $major
     */
    public function removeMajor(\App\Entity\User\Info\Major $major) {
        $this->majors->removeElement($major);
    }

    /**
     * Add specialty
     *
     * @return Info
     */
    public function updateSpecialty(Subject $subject, int $rating) {
        foreach($this->specialties as $specialty) {
            if($specialty->getSubject() == $subject) {
                $specialty->updateRating($rating);
                return $this;
            }
        }

        $this->specialties[] = new Specialty($this, $subject, $rating);

        return $this;
    }


    /**
     * Get specialty
     */
    public function getSpecialty(Subject $subject) {
        foreach($this->specialties as $specialty) {
            if($specialty->getSubject() == $subject) {
                return $specialty->getRating();
            }
        }

        return null;
    }

    public function getSpecialties() {
        return $this->specialties;
    }

    /**
     * Get id
     *
     * @return guid
     */
    public function getId() {
        return $this->user->getId();
    }

    public function getUploadedImage() {
        return $this->uploadedImage;
    }

    public function setUploadedImage($image) {
        $this->uploadedImage = $image;
    }
}
