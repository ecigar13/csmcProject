<?php

namespace App\Entity\User\Info;

use App\Entity\File\File;
use App\Entity\User\User;
use App\Form\Data\ProfileFormData;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_profile")
 * @see ProfileFormData is the class used to build forms and validate data from this class.
 */
class Profile
{
    /**
     * @ORM\Id()
     * @ORM\OneToOne(targetEntity="App\Entity\User\User", inversedBy="profile")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=17, nullable=true)
     */
    private $preferredName;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\File\File")
     * @ORM\JoinColumn(name="image_file_id", referencedColumnName="id")
     */
    private $image;

    /**
     * We can't use separate fields for each type of request because of a doctrine limitation.
     * Luckily we can enforce that only one request of each type exists in the database,
     * @see ProfileModificationRequest.
     *
     * Setting @see OneToMany::$orphanRemoval to true allows us to simply remove items from the collection and let the ORM
     * remove them from the database for us.
     *
     * @ORM\OneToMany(targetEntity="App\Entity\User\Info\ProfileModificationRequest",
     *     mappedBy="profile",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     * @var ProfileModificationRequest[]
     */
    private $modificationRequests;

    /**
     * @ORM\OneToMany(targetEntity="Specialty", mappedBy="profile", cascade={"persist"})
     */
    private $specialties;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $birthDate;

    /**
     * @ORM\Column(type="graduation_semester", nullable=true)
     */
    private $expectedGraduationSemester;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="text", length=250, nullable=true)
     */
    private $dietaryRestrictions;

    /**
     * @ORM\Column(type="text", length=250, nullable=true)
     *
     * @var string|null
     */
    private $adminNotes;

    private function __construct(User $user)
    {
        $this->user = $user;
        $this->specialties = new ArrayCollection();
        $this->modificationRequests = new ArrayCollection();
    }

    public static function createForUser(User $user)
    {
        $profile = new Profile($user);
        return $profile;
    }

    /**
     * This method exists not only to conveniently update the object from the form data in one step, but also to
     * preserve encapsulation. Having an update method for each field would force clients to know the internal
     * structure and functionality of this class in order to use it.
     *
     * @param ProfileFormData $formData
     * @param bool $adminOverride If this is `true`, no modification requests for any field will be created, instead all
     * the fields will be immediately updated.
     */
    public function updateFromFormData(ProfileFormData $formData, bool $adminOverride = false)
    {
        $this->updatePreferredName($adminOverride, $formData->getPreferredName());

        $this->birthDate = $formData->getBirthDate();
        $this->expectedGraduationSemester = $formData->getExpectedGraduationSemester();
        $this->phoneNumber = $formData->getPhoneNumber();
        $this->dietaryRestrictions = $formData->getDietaryRestrictions();
        $this->adminNotes = $formData->getAdminNotes();
        $this->user->updateNotificationPreferences($formData->getNotificationPreferences());

        // The form contains all the specialties in the database so iterate over that and not the specialties field
        foreach ($formData->getSpecialties() as $specialtyForm) {
            $foundSpecialtyEntity = false;
            foreach ($this->specialties as $specialty) {
                // If the ID is null it means it was newly created, and the next if would return true which would not be correct
                if ($specialty->getId() == null) {
                    continue;
                }

                if ($specialty->getId() === $specialtyForm->getId()) {
                    $specialty->updateFromFormData($specialtyForm);
                    $foundSpecialtyEntity = true;
                    break;
                }
            }

            // If we didn't find it is a new entity that needs to be added
            if (!$foundSpecialtyEntity) {
                $this->specialties[] = Specialty::createFromFormData($specialtyForm);
            }
        }
    }

    /**
     * @param bool $adminOverride If this is `true`, the name will be updated without creating a request.
     * @param string $newPreferredName
     */
    private function updatePreferredName(bool $adminOverride, string $newPreferredName = null)
    {
        if ($adminOverride) {
            $this->preferredName = $newPreferredName;
            return;
        }

        if ($newPreferredName == null || empty($newPreferredName)) {
            // In this case the user wants to reset the preferred name to the default, so just make it null
            $this->preferredName = null;

            // If the field had a pending modification request, remove it since using the default needs no authorization
            $modificationRequest = $this->findModificationRequestByType(PreferredNameModificationRequest::class);
            if ($modificationRequest != null) {
                $this->modificationRequests->removeElement($modificationRequest);
            }
        } else {
            $this->handleModificationRequest(PreferredNameModificationRequest::class,
                $this->preferredName,
                $newPreferredName,
                function ($profile) {
                    return new PreferredNameModificationRequest($profile);
                });
        }
    }

    /**
     * Handles the common aspects of a profile modification that needs admin approval.
     *
     * @param string $requestClass
     * @param $oldValue
     * @param string $newValue
     * @param $newRequestCallback
     */
    private function handleModificationRequest(string $requestClass, $oldValue, $newValue, $newRequestCallback)
    {
        $modificationRequest = $this->findModificationRequestByType($requestClass);

        if ($newValue != $oldValue) {
            if ($modificationRequest == null) {
                // The user wants to change the value of the field and a modification request does not already exist
                /** @var ProfileModificationRequest $newModificationRequest */
                $newModificationRequest = $newRequestCallback($this);
                $newModificationRequest->update($newValue);
                $this->modificationRequests[] = $newModificationRequest;
            } else {
                // If a request already exists, update it
                $modificationRequest->update($newValue);
            }
        } elseif ($modificationRequest != null) {
            // The user changed the value to what it was before the request, so just remove the request
            $this->modificationRequests->removeElement($modificationRequest);
        }

        // The value is the same and a request doesn't exist: do nothing
    }

    public function approveModificationRequest(ProfileModificationRequest $request)
    {
        if ($request instanceof PreferredNameModificationRequest) {
            $this->preferredName = $request->getValue();    //if changes are made to preferred name
        } elseif ($request instanceof ProfilePictureModificationRequest) {
            $this->image = $request->getValue();            //if changes are made to image
        }

        //delete request persistence
        $this->modificationRequests->removeElement($request);
    }

    public function rejectModificationRequest(ProfileModificationRequest $request)
    {
        $this->modificationRequests->removeElement($request);
    }

    public function getPreferredName()
    {
        return $this->preferredName;
    }

    /**
     * @return PreferredNameModificationRequest|null
     */
    public function getPreferredNameModificationRequest()
    {
        return $this->findModificationRequestByType(PreferredNameModificationRequest::class);
    }

    private function findModificationRequestByType(string $type)
    {
        foreach ($this->modificationRequests as $modificationRequest) {
            if ($modificationRequest instanceof $type) {
                return $modificationRequest;
            }
        }

        return null;
    }

    /**
     * @return File|null
     */
    public function getProfilePicture()
    {
        return $this->image;
    }

    /**
     * @return ProfilePictureModificationRequest|null
     */
    public function getProfilePictureModificationRequest()
    {
        return $this->findModificationRequestByType(ProfilePictureModificationRequest::class);
    }

    /**
     * We keep this method separate from @see updateFromFormData because the image update happens via AJAX, so the data
     * will not be available at the time the form is submitted.
     *
     * @param File $image
     * @param bool $adminOverride If `true`, the image will be updated immediately without creating a modification
     * request.
     */
    public function updateProfilePicture(File $image, bool $adminOverride = false)
    {
        if ($adminOverride) {
            $this->image = $image;
            return;
        }

        $this->handleModificationRequest(ProfilePictureModificationRequest::class,
            $this->image,
            $image,
            function ($profile) {
                return new ProfilePictureModificationRequest($profile);
            });
    }

    /**
     * @return mixed
     */
    public function getSpecialties()
    {
        return $this->specialties;
    }

    /**
     * @return mixed
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * @return mixed
     */
    public function getExpectedGraduationSemester()
    {
        return $this->expectedGraduationSemester;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @return mixed
     */
    public function getDietaryRestrictions()
    {
        return $this->dietaryRestrictions;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return null|string
     */
    public function getAdminNotes()
    {
        return $this->adminNotes;
    }

}