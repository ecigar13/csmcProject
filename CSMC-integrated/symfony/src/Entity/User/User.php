<?php

namespace App\Entity\User;

use App\Entity\File\File;
use App\Entity\File\VirtualFile;
use App\Entity\Misc\Subject;
use App\Entity\Misc\Visit;
use App\Entity\Occurrence\AttendanceOccurrence;
use App\Entity\Occurrence\BehaviorOccurrence;
use App\Entity\Occurrence\Occurrence;
use App\Entity\User\Info\NotificationPreferences;
use App\Entity\User\Info\Info;
use App\Entity\User\Info\Profile;
use App\Form\Data\NotificationPreferencesFormData;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\User\UserRepository")
 *
 * @UniqueEntity(
 *     fields = {"username"},
 *     message = "This NetID {{ value }} already exists!"
 * )
 */
class User implements UserInterface, \Serializable {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="first_name", length=32, nullable=true)
     *
     * @Assert\Length(
     *      min = 1,
     *      max = 32,
     *      maxMessage = "The first name cannot be longer than {{ limit }} characters!"
     * )
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", name="last_name", length=64, nullable=true)
     *
     * @Assert\Length(
     *      min = 1,
     *      max = 64,
     *      maxMessage = "The last name cannot be longer than {{ limit }} characters!"
     * )
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", name="netid", length=9, unique=true)
     *
     * @Assert\Length(
     *     min = 3,
     *     max = 9,
     *     minMessage = "The NetID must be at least {{ limit }} characters!",
     *     maxMessage = "The NetID cannot be longer than {{ limit }} characters!"
     * )
     */
    private $username;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User\Info\Profile", mappedBy="user", cascade={"persist"})
     */
    private $profile;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User\Info\Info", mappedBy="user", cascade={"persist"})
     */
    private $info;

    /**
     * @ORM\Column(type="string", name="scancode", length=16, unique=true, nullable=true)
     *
     * @Assert\Length(
     *     min = 0,
     *     max = 16,
     *     maxMessage = "The scan code need to be exactly {{ limit }} characters!"
     * )
     */
    // TODO check if min can be 16
    private $scancode;

    /**
     * @ORM\Column(type="string", name="card_id", length=11, unique=true, nullable=true, options={"comment":"Card ID as given by scanner"})
     *
     * @Assert\Length(
     *     min = 0,
     *     max = 11,
     *     maxMessage = "The card id should be exactly {{ limit }} characters!"
     * )
     */
    // TODO check if min can be 9
    private $cardId;

    /**
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="users")
     * @ORM\JoinTable(name="user_roles")
     */
    private $roles;

    /**
     * @ORM\ManyToMany(targetEntity="UserGroup", inversedBy="users")
     */
    private $groups;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Misc\Visit", mappedBy="user", cascade={"persist"})
     */
    private $visits;

    /**
     * Constructor
     * @ORM\OneToMany(targetEntity="App\Entity\Occurrence\Occurrence", mappedBy="subject", cascade={"persist"})
     * @var Occurrence[]
     */
    private $occurrences;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User\Info\NotificationPreferences",
     *     mappedBy="user",
     *     cascade={"persist"}
     * )
     *
     * @var NotificationPreferences
     */
    private $notificationPreferences;

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $username
     */
    public function __construct(string $firstName, string $lastName, string $username)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->username = $username;

        $this->profile = Profile::createForUser($this);
        $this->notificationPreferences = new NotificationPreferences($this);
        $this->info = Info::createForUser($this);

        $this->roles = new ArrayCollection();
        $this->occurrences = new ArrayCollection();
    }

    public function addOccurrence(Occurrence $occurrence)
    {
        $this->occurrences->add($occurrence);
    }

    /**
     * @param NotificationPreferencesFormData $formData
     */
    public function updateNotificationPreferences(NotificationPreferencesFormData $formData)
    {
        $this->notificationPreferences->updateFromFormData($formData);
    }

    // FIXME: using this method to aggregate points is very slow, use aggregate fields instead
    public function getBehaviorOccurrencePoints()
    {
        $total = 0;

        foreach ($this->occurrences as $occurrence) {
            if ($occurrence instanceof BehaviorOccurrence && $occurrence->getStatus() == Occurrence::STATUS_APPROVED) {
                $total += $occurrence->getPoints();
            }
        }

        return $total;
    }

    public function getAttendanceOccurrencePoints()
    {
        $total = 0;

        foreach ($this->occurrences as $occurrence) {
            if ($occurrence instanceof AttendanceOccurrence && $occurrence->getStatus() == Occurrence::STATUS_APPROVED) {
                $total += $occurrence->getPoints();
            }
        }

        return $total;
    }

    public function getTotalOccurrencePoints()
    {
        return $this->getBehaviorOccurrencePoints() + $this->getAttendanceOccurrencePoints();
    }

    public function getProfile()
    {
        return $this->profile;
    }

    public function createProfile() {
        $this->profile = Profile::createForUser($this);
    }

    public function createInfo() {
        $this->info = Info::createForUser($this);
    }

    public function __toString() {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName() {
        return $this->firstName;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName() {
        return $this->lastName;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Get scancode
     *
     * @return string
     */
    public function getScancode() {
        return $this->scancode;
    }

    /**
     * Get cardId
     *
     * @return string
     */
    public function getCardId() {
        return $this->cardId;
    }

    /**
     * Add role
     *
     * @param \App\Entity\User\Role $role
     *
     * @return User
     */
    public function addRole(Role $role) {
        $this->roles [] = $role;

        return $this;
    }

    /**
     * Remove role
     *
     * @param \App\Entity\User\Role $role
     */
    public function removeRole(Role $role) {
        $this->roles->removeElement($role);
    }

    /**
     * Get roles
     *
     * Returns role strings
     *
     * @return Role[]
     */
    public function getRoles() {
        return $this->roles->toArray();
    }

    /**
     * Get user roles
     *
     * @return
     */
    public function getUserRoles() {
        return $this->roles;
    }

    /**
     * Check if user has role
     */
    public function hasRole(string $role) {
        foreach ($this->roles as $r) {
            if ($r->getName() == $role) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get info
     *
     * @return \App\Entity\User\Info\Info
     */
    public function getInfo() {
        return $this->info;
    }

    /**
     * Add visit
     *
     * @param \App\Entity\Misc\Visit $visit
     *
     * @return User
     */
    public function addVisit(Visit $visit) {
        $this->visits[] = $visit;

        return $this;
    }

    /**
     * Remove visit
     *
     * @param \App\Entity\Misc\Visit $visit
     */
    public function removeVisit(Visit $visit) {
        $this->visits->removeElement($visit);
    }

    /**
     * Get visits
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVisits() {
        return $this->visits;
    }

    public function getPreferredName() {
        if ($this->profile == null || $this->profile->getPreferredName() == null) {
            return $this->firstName;
        }

        return $this->profile->getPreferredName();
    }

    public function getEmail() {
        return ($this->notificationPreferences && $this->notificationPreferences->getPreferredEmail())
            ? $this->notificationPreferences->getPreferredEmail()
            : ($this->username . '@utdallas.edu');
    }

    public function updateSpecialty(Subject $subject, int $rating) {
        $this->info->updateSpecialty($subject, $rating);

        return $this;
    }

    public function getProfilePicture() {
        if($this->profile == null) {
            return null;
        }
        return $this->profile->getProfilePicture();
    }

    public function updateProfilePicture(File $image, bool $adminOverride = false)
    {
        $this->profile->updateProfilePicture($image, $adminOverride);
    }

    public function updateCardId(string $code, bool $legacy) {
        if ($legacy) {
            $this->scancode = $code;
        } else {
            $this->cardId = $code;
        }

        return $this;
    }

    public function serialize() {
        return json_encode(
            array(
                $this->id,
                $this->firstName,
                $this->lastName,
                $this->username,
                $this->scancode,
                $this->roles
            ));
    }

    public function unserialize($serialized) {
        list ($this->id, $this->firstName, $this->lastName, $this->username, $this->scancode, $this->roles) = json_decode(
            $serialized);
    }

    public function compareMentors($mentor1, $mentor2) {
        return strcmp($mentor1->firstName, $mentor2->firstName);
    }

    /**
     * Unused
     */
    public function getPassword() {
        return null;
    }

    /**
     * Unused
     *
     * Must return null
     */
    public function getSalt() {
        return null;
    }

    /**
     * Unused
     *
     * Must return null
     */
    public function eraseCredentials() {
        return null;
    }

    /**
     * @return NotificationPreferences
     */
    public function getNotificationPreferences(): NotificationPreferences
    {
        return $this->notificationPreferences;
    }
}
