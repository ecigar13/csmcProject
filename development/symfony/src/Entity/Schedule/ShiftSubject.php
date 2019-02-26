<?php

namespace App\Entity\Schedule;

use App\Entity\Misc\Subject;
use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity
 * @ORM\Table(name="shift_subject")
 */
class ShiftSubject {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Misc\Subject")
     * @ORM\JoinColumn(name="subject_id", referencedColumnName="id")
     */
    private $subject;

    /**
     * @ORM\Column(type="smallint", name="max_mentors")
     */
    private $maxMentors;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User\User")
     * @ORM\JoinTable(name="shift_subject_mentors",
     *     joinColumns={@ORM\JoinColumn(name="shift_subject_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="mentor_id", referencedColumnName="id")}
     * )
     */
    private $mentors;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Schedule\Shift", inversedBy="subjects")
     * @ORM\JoinColumn(name="shift_id", referencedColumnName="id")
     */
    private $shift;

    /**
     * Constructor
     */
    public function __construct(Subject $subject, int $max) {
        $this->subject = $subject;
        $this->maxMentors = $max;

        $this->mentors = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return guid
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set maxMentors
     *
     * @param integer $maxMentors
     *
     * @return ShiftSubject
     */
    public function setMaxMentors($maxMentors) {
        $this->maxMentors = $maxMentors;

        return $this;
    }

    /**
     * Get maxMentors
     *
     * @return integer
     */
    public function getMaxMentors() {
        return $this->maxMentors;
    }

    /**
     * Set subject
     *
     * @param \App\Entity\Misc\Subject $subject
     *
     * @return ShiftSubject
     */
    public function setSubject(Subject $subject = null) {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return \App\Entity\Misc\Subject
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * Add mentor
     *
     * @param \App\Entity\User\User $mentor
     *
     * @return ShiftSubject
     */
    public function addMentor(User $mentor) {
        $this->mentors[] = $mentor;

        return $this;
    }

    /**
     * Remove mentor
     *
     * @param \App\Entity\User\User $mentor
     */
    public function removeMentor(User $mentor) {
        $this->mentors->removeElement($mentor);
    }

    /**
     * Get mentors
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMentors() {
        return $this->mentors;
    }

    /**
     * Set shift
     *
     * @param \App\Entity\Schedule\Shift $shift
     *
     * @return ShiftSubject
     */
    public function setShift(Shift $shift = null) {
        $this->shift = $shift;

        return $this;
    }

    /**
     * Get shift
     *
     * @return \App\Entity\Schedule\Shift
     */
    public function getShift() {
        return $this->shift;
    }
}
