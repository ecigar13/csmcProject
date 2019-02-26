<?php

namespace App\Form\Data;

/**
 * This class should only be used to initialize and retrieve data from a form.
 *
 * @package App\Form\Data
 * @see BehaviorOccurrence is the entity that corresponds to this class.
 */
class BehaviorOccurrenceFormData
{
    /**
     * @var string[]
     */
    private $subject;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $details;

    /**
     * @var string
     */
    private $dateOfOccurrence;

    /**
     * @var bool
     */
    private $anonymous;

    /**
     * @return string[]
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return null|string
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param null|string $details
     */
    public function setDetails(string $details)
    {
        $this->details = $details;
    }

    /**
     * @return string
     */
    public function getDateOfOccurrence()
    {
        return $this->dateOfOccurrence;
    }

    /**
     * @param string $dateOfOccurrence
     */
    public function setDateOfOccurrence(string $dateOfOccurrence)
    {
        $this->dateOfOccurrence = $dateOfOccurrence;
    }

    /**
     * @return bool
     */
    public function isAnonymous(): bool
    {
        return $this->anonymous;
    }

    /**
     * @param bool $anonymous
     */
    public function setAnonymous(bool $anonymous)
    {
        $this->anonymous = $anonymous;
    }

}
