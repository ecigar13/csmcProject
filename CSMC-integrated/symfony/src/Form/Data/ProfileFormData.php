<?php


namespace App\Form\Data;


use App\DataType\GraduationSemester;
use App\Entity\Misc\Subject;
use App\Entity\User\Info\Profile;
use App\Entity\User\Info\Specialty;
use App\Entity\User\User;
use App\Validator\Constraints as MMSAssert;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This class should only be used to initialize and retrieve data from a form.
 *
 * @package App\Form\Data
 * @see Profile is the entity that corresponds to this class.
 */
class ProfileFormData
{
    /**
     * @var User
     */
    private $user;

    /**
     * @Assert\Length(
     *     max = 17,
     *     maxMessage = "Preferred name cannot be longer than {{ limit }} characters"
     * )
     *
     * @var string|null
     */
    private $preferredName;

    /**
     * @var SpecialtyFormData[]
     */
    private $specialties;

    /**
     * @Assert\LessThan(
     *     value = "today",
     *     message = "Birth date cannot be in the future"
     * )
     *
     * @Assert\NotNull
     *
     * @var \DateTime|null
     */
    private $birthDate;

    /**
     * @MMSAssert\IsGraduationSemester
     *
     * @Assert\NotNull
     *
     * @var GraduationSemester|null
     */
    private $expectedGraduationSemester;

    /**
     * @Assert\Regex(
     *     pattern = "/^\d{10}$/",
     *     message = "Phone number must be exactly 10 digits long"
     * )
     *
     * @Assert\NotBlank
     *
     * @var string|null
     */
    private $phoneNumber;

    /**
     * @Assert\Length(
     *     max = 250,
     *     maxMessage = "Dietary restrictions cannot be longer than {{ limit }} characters"
     * )
     *
     * @var string|null
     */
    private $dietaryRestrictions;

    /**
     * @Assert\Length(
     *     max = 250,
     *     maxMessage="Admin notes field cannot be longer than {{ limit }} characters"
     * )
     * @var string|null
     */
    private $adminNotes;

    /**
     * @Assert\Valid
     *
     * @var NotificationPreferencesFormData
     */
    private $notificationPreferences;

    /**
     * Should not be directly instantiated
     */
    private function __construct()
    {
    }

    /**
     * @param Profile $profile that will correspond to the new object
     * @param ObjectManager $objectManager is required because we need to display all the subjects every time the edit
     * form is displayed.
     * @return ProfileFormData corresponding to the provided profile
     */
    public static function createFromProfile(Profile $profile, ObjectManager $objectManager)
    {
        $formData = new self();

        $formData->user = $profile->getUser();

        // If a modification request exists, we should display it in the form
        $modificationRequest = $profile->getPreferredNameModificationRequest();
        if ($modificationRequest != null) {
            $formData->preferredName = $modificationRequest->getValue();
        } else {
            $formData->preferredName = $profile->getPreferredName();
        }

        $formData->birthDate = $profile->getBirthDate();
        $formData->expectedGraduationSemester = $profile->getExpectedGraduationSemester();
        $formData->phoneNumber = $profile->getPhoneNumber();
        $formData->dietaryRestrictions = $profile->getDietaryRestrictions();
        $formData->adminNotes = $profile->getAdminNotes();
        $formData->notificationPreferences =
            NotificationPreferencesFormData::createFromNotificationPreferences($profile->getUser()->getNotificationPreferences());

        // Specialties also need to be transformed to form data objects
        $specialtyForms = array();
        foreach ($profile->getSpecialties() as $specialty) {
            $specialtyForms[] = SpecialtyFormData::createFromSpecialty($specialty);
        }

        // Some new subjects might exist in the database: we need to create new specialty forms for them
        // Get all subject IDs
        $ratedSubjectIDs = array_map(function ($x) {
            /** @var Specialty $x */
            return $x->getSubject()->getId();
        }, $profile->getSpecialties()->getValues()); // getValues is a very poorly named way of converting this collection to an array

        foreach ($objectManager->getRepository(Subject::class)->findAll() as $subject) {
            if (!in_array($subject->getId(), $ratedSubjectIDs)) {
                // The subject is not rated: create a new specialty data object for it using the default rating
                $specialtyForms[] = SpecialtyFormData::createFromSpecialty(new Specialty($profile->getUser()->getInfo(), $subject));
            }
        }

        $formData->specialties = $specialtyForms;

        return $formData;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return null|string
     */
    public function getPreferredName()
    {
        return $this->preferredName;
    }

    /**
     * @param null|string $preferredName
     */
    public function setPreferredName(string $preferredName = null)
    {
        $this->preferredName = $preferredName;
    }

    /**
     * @return SpecialtyFormData[]
     */
    public function getSpecialties()
    {
        return $this->specialties;
    }

    /**
     * @param SpecialtyFormData[] $specialties
     */
    public function setSpecialties(array $specialties)
    {
        $this->specialties = $specialties;
    }

    /**
     * @return \DateTime|null
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * @param \DateTime|null $birthDate
     */
    public function setBirthDate(\DateTime $birthDate = null)
    {
        $this->birthDate = $birthDate;
    }

    /**
     * @return GraduationSemester|null
     */
    public function getExpectedGraduationSemester()
    {
        return $this->expectedGraduationSemester;
    }

    /**
     * @param GraduationSemester $expectedGraduationSemester
     */
    public function setExpectedGraduationSemester(GraduationSemester $expectedGraduationSemester = null)
    {
        $this->expectedGraduationSemester = $expectedGraduationSemester;
    }

    /**
     * @return null|string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param null|string $phoneNumber
     */
    public function setPhoneNumber(string $phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return null|string
     */
    public function getDietaryRestrictions()
    {
        return $this->dietaryRestrictions;
    }

    /**
     * @param null|string $dietaryRestrictions
     */
    public function setDietaryRestrictions(string $dietaryRestrictions)
    {
        $this->dietaryRestrictions = $dietaryRestrictions;
    }

    /**
     * @return null|string
     */
    public function getAdminNotes()
    {
        return $this->adminNotes;
    }

    /**
     * @param null|string $adminNotes
     */
    public function setAdminNotes(string $adminNotes)
    {
        $this->adminNotes = $adminNotes;
    }

    /**
     * @return NotificationPreferencesFormData
     */
    public function getNotificationPreferences(): NotificationPreferencesFormData
    {
        return $this->notificationPreferences;
    }

    /**
     * @param NotificationPreferencesFormData $notificationPreferences
     */
    public function setNotificationPreferences(NotificationPreferencesFormData $notificationPreferences)
    {
        $this->notificationPreferences = $notificationPreferences;
    }

}
