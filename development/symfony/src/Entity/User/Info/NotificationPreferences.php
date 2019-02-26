<?php


namespace App\Entity\User\Info;


use App\Entity\User\User;
use App\Form\Data\NotificationPreferencesFormData;
use Doctrine\ORM\Mapping as ORM;

/**
 * @see NotificationPreferencesFormData is the form data class that corresponds to this entity.
 *
 * @ORM\Entity
 * @package App\Entity\User\Info
 */
class NotificationPreferences
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="App\Entity\User\User", inversedBy="notificationPreferences")
     *
     * @var User
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=254, nullable=true)
     *
     * @var string|null
     */
    private $preferredEmail;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $useEmail = false;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     *
     * @var string|null
     */
    private $preferredPhoneNumber;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @var string|null
     */
    private $preferredPhoneNumberCarrier;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $usePhoneNumber = false;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $notifyWhenAssigned = false;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $notifyBeforeSession = false;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @var int|null
     */
    private $sessionReminderAdvanceDays;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param NotificationPreferencesFormData $formData
     */
    public function updateFromFormData(NotificationPreferencesFormData $formData)
    {
        $this->preferredEmail = $formData->getPreferredEmail();
        $this->useEmail = $formData->isUseEmail();
        $this->preferredPhoneNumber = $formData->getPreferredPhoneNumber();
        $this->preferredPhoneNumberCarrier = $formData->getPreferredPhoneNumberCarrier();
        $this->usePhoneNumber = $formData->isUsePhoneNumber();
        $this->notifyWhenAssigned = $formData->isNotifyWhenAssigned();
        $this->notifyBeforeSession = $formData->isNotifyBeforeSession();
        $this->sessionReminderAdvanceDays = $formData->getSessionReminderAdvanceDays();

        if (!$this->hasNotifications()) {
            $this->preferredPhoneNumberCarrier = null;
            $this->sessionReminderAdvanceDays = null;
            $this->useEmail = false;
            $this->usePhoneNumber = false;
        }
    }

    /**
     * @return bool `true` if notifications of at least one kind are enabled.
     */
    public function hasNotifications()
    {
        return $this->notifyBeforeSession || $this->notifyWhenAssigned;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPreferredEmail()
    {
        if ($this->preferredEmail != null) {
            return $this->preferredEmail;
        } else {
            return $this->user->getUsername() . '@utdallas.edu';
        }
    }

    /**
     * @return bool
     */
    public function isUseEmail(): bool
    {
        return $this->useEmail;
    }

    /**
     * @return string
     */
    public function getPreferredPhoneNumber()
    {
        if ($this->preferredPhoneNumber != null) {
            return $this->preferredPhoneNumber;
        }else{
            return $this->user->getProfile()->getPhoneNumber();
        }
    }

    /**
     * @return null|string
     */
    public function getPreferredPhoneNumberCarrier()
    {
        return $this->preferredPhoneNumberCarrier;
    }

    /**
     * @return bool
     */
    public function isUsePhoneNumber(): bool
    {
        return $this->usePhoneNumber;
    }

    /**
     * @return bool
     */
    public function isNotifyWhenAssigned(): bool
    {
        return $this->notifyWhenAssigned;
    }

    /**
     * @return bool
     */
    public function isNotifyBeforeSession(): bool
    {
        return $this->notifyBeforeSession;
    }

    /**
     * @return int|null
     */
    public function getSessionReminderAdvanceDays()
    {
        return $this->sessionReminderAdvanceDays;
    }

}