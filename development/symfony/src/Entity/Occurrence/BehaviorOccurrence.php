<?php

namespace App\Entity\Occurrence;

use App\Entity\User\User;
use App\Form\Data\BehaviorOccurrenceFormData;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @see BehaviorOccurrenceFormData is the class used to build forms and validate data from this class.
 */
class BehaviorOccurrence extends Occurrence
{
    // TODO: link with the types defined in the settings page
    /**
     * @ORM\Column(type="string")
     */
    private $type;

    /**
     * @ORM\Column(type="text")
     *
     * @var string|null
     */
    private $details;

    /**
     * @ORM\Column(type="datetime")
     *
     * @var \DateTime
     */
    private $reportedDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User")
     *
     * @var User|null
     */
    private $submitter;

    /**
     * @param User $subject
     * @param $type
     * @param string $details
     * @param \DateTime $reportedDate
     * @param User $submitter
     */
    function __construct(User $subject, $type, string $details, \DateTime $reportedDate, User $submitter = null)
    {
        parent::__construct($subject);

        $this->type = $type;
        $this->details = $details;
        $this->reportedDate = $reportedDate;
        $this->submitter = $submitter;

        // TODO: get points from type
        $this->setPoints(0.5);
    }


    // FIXME: this method needs to be properly implemented and maybe just turn it into a constructor
    // public static function createFromFormData(BehaviorOccurrenceFormData $formData)
    // {
    //     $occurrence = new BehaviorOccurrence($formData->getSubject(), $formData->getType(), $formData->getDetails(),
    //         $formData->getDateOfOccurrence(), "");

    //     return $occurrence;
    // }

    // public function updateFromFormData(OccurrenceSubmissionFormData $formData)
    // {
    //     $this->subject = $formData->getSubject();
    //     $this->type = $formData->getType();
    //     $this->details = $formData->getDetails();
    //     $this->dateOfOccurrence = $formData->getDateOfOccurrence();
    //     $this->anonymity = $formData->getAnonymity();
    // }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return null|string
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @return \DateTime
     */
    public function getReportedDate(): \DateTime
    {
        return $this->reportedDate;
    }

    /**
     * @return User|null
     */
    public function getSubmitter()
    {
        return $this->submitter;
    }

    public function setType(string $type) {
        $this->type = $type;
    }

}