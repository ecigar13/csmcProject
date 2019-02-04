<?php


namespace App\Form\Data;


use App\Entity\User\Info\NotificationPreferences;
use App\Entity\User\Info\Profile;
use App\Form\NotificationPreferencesType;
use App\Utils\SMSEmailGateway;
use App\Validator\Constraints\IsNotificationPreferences;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Form data class for @see NotificationPreferences
 *
 * @see NotificationPreferencesType
 * @package App\Form\Data
 */
class NotificationPreferencesFormData
{
    /**
     * @var Profile
     */
    private $profile;

    /**
     * @Assert\Length(
     *     max="254",
     *     maxMessage="Email cannot be longer than {{ limit }} characters"
     * )
     * @Assert\Email(message="Value should be a valid email")
     *
     * @var string|null
     */
    private $preferredEmail;

    /**
     * @var bool
     */
    private $useEmail;

    /**
     * @Assert\Regex(
     *     pattern = "/^\d{10}$/",
     *     message = "Phone number must be in a valid format"
     * )
     *
     * @var string|null
     */
    private $preferredPhoneNumber;

    /**
     * @Assert\Choice(callback="getPhoneNumberCarrierChoices")
     *
     * @var string|null
     */
    private $preferredPhoneNumberCarrier;

    /**
     * @var bool
     */
    private $usePhoneNumber;

    /**
     * @var bool
     */
    private $notifyWhenAssigned;

    /**
     * @var bool
     */
    private $notifyBeforeSession;

    /**
     * @Assert\GreaterThanOrEqual(value=0)
     *
     * @var int|null
     */
    private $sessionReminderAdvanceDays;

    /**
     * @param NotificationPreferences $notificationPreferences
     * @return NotificationPreferencesFormData
     */
    public static function createFromNotificationPreferences(NotificationPreferences $notificationPreferences)
    {
        $formData = new self();

        $formData->profile = $notificationPreferences->getUser();
        $formData->preferredEmail = $notificationPreferences->getPreferredEmail();
        $formData->useEmail = $notificationPreferences->isUseEmail();
        $formData->preferredPhoneNumber = $notificationPreferences->getPreferredPhoneNumber();
        $formData->preferredPhoneNumberCarrier = $notificationPreferences->getPreferredPhoneNumberCarrier();
        $formData->usePhoneNumber = $notificationPreferences->isUsePhoneNumber();
        $formData->notifyWhenAssigned = $notificationPreferences->isNotifyWhenAssigned();
        $formData->notifyBeforeSession = $notificationPreferences->isNotifyBeforeSession();
        $formData->sessionReminderAdvanceDays = $notificationPreferences->getSessionReminderAdvanceDays();

        return $formData;
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (!$this->notifyBeforeSession && !$this->notifyWhenAssigned) {
            // If there are no notifications enabled, there is no need to validate
            return;
        }

        if ($this->usePhoneNumber &&
            ($this->preferredPhoneNumberCarrier == null || $this->preferredPhoneNumber == null)) {
            $context->buildViolation('If text notifications are enabled, both preferred phone number and carrier must be provided')
                ->atPath('preferredPhoneNumber')
                ->addViolation();
        }

        if (!$this->usePhoneNumber && !$this->useEmail) {
            $context->buildViolation('Please select a preferred delivery method if you want to be notified')
                ->atPath('useEmail')
                ->addViolation();
        }

        if ($this->notifyBeforeSession && $this->sessionReminderAdvanceDays === null) {
            $context->buildViolation('If notifications before a session are enabled, you must input how many days in advance they should be sent')
                ->atPath('sessionReminderAdvanceDays')
                ->addViolation();
        }
    }

    public static function getPhoneNumberCarrierChoices()
    {
        return array_keys(SMSEmailGateway::CARRIER_EMAIL_GATEWAY_ADDRESSES);
    }

    /**
     * @return Profile
     */
    public function getProfile(): Profile
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
     * @return null|string
     */
    public function getPreferredEmail()
    {
        return $this->preferredEmail;
    }

    /**
     * @param null|string $preferredEmail
     */
    public function setPreferredEmail(string $preferredEmail = null)
    {
        $this->preferredEmail = $preferredEmail;
    }

    /**
     * @return bool
     */
    public function isUseEmail(): bool
    {
        return $this->useEmail;
    }

    /**
     * @param bool $useEmail
     */
    public function setUseEmail(bool $useEmail)
    {
        $this->useEmail = $useEmail;
    }

    /**
     * @return null|string
     */
    public function getPreferredPhoneNumber()
    {
        return $this->preferredPhoneNumber;
    }

    /**
     * @param null|string $preferredPhoneNumber
     */
    public function setPreferredPhoneNumber(string $preferredPhoneNumber = null)
    {
        $this->preferredPhoneNumber = $preferredPhoneNumber;
    }

    /**
     * @return string|null
     */
    public function getPreferredPhoneNumberCarrier()
    {
        return $this->preferredPhoneNumberCarrier;
    }

    /**
     * @param string $preferredPhoneNumberCarrier
     */
    public function setPreferredPhoneNumberCarrier(string $preferredPhoneNumberCarrier = null)
    {
        $this->preferredPhoneNumberCarrier = $preferredPhoneNumberCarrier;
    }

    /**
     * @return bool
     */
    public function isUsePhoneNumber(): bool
    {
        return $this->usePhoneNumber;
    }

    /**
     * @param bool $usePhoneNumber
     */
    public function setUsePhoneNumber(bool $usePhoneNumber)
    {
        $this->usePhoneNumber = $usePhoneNumber;
    }

    /**
     * @return bool
     */
    public function isNotifyWhenAssigned(): bool
    {
        return $this->notifyWhenAssigned;
    }

    /**
     * @param bool $notifyWhenAssigned
     */
    public function setNotifyWhenAssigned(bool $notifyWhenAssigned)
    {
        $this->notifyWhenAssigned = $notifyWhenAssigned;
    }

    /**
     * @return bool
     */
    public function isNotifyBeforeSession(): bool
    {
        return $this->notifyBeforeSession;
    }

    /**
     * @param bool $notifyBeforeSession
     */
    public function setNotifyBeforeSession(bool $notifyBeforeSession)
    {
        $this->notifyBeforeSession = $notifyBeforeSession;
    }

    /**
     * @return int|null
     */
    public function getSessionReminderAdvanceDays()
    {
        return $this->sessionReminderAdvanceDays;
    }

    /**
     * @param int|null $sessionReminderAdvanceDays
     */
    public function setSessionReminderAdvanceDays(int $sessionReminderAdvanceDays = null)
    {
        $this->sessionReminderAdvanceDays = $sessionReminderAdvanceDays;
    }

}