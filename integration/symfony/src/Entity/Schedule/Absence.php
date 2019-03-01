<?php

namespace App\Entity\Schedule;

use App\Entity\Interfaces\ModifiableInterface;
use App\Entity\Traits\ModifiableTrait;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Schedule\AbsenceRepository")
 * @ORM\Table(name="absence")
 *
 * @Serializer\ExclusionPolicy("all")
 *
 * @Serializer\VirtualProperty(
 *     "assignment",
 *     exp="object.getAssignment().getId()"
 * )
 */
class Absence implements ModifiableInterface {
    use ModifiableTrait;
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @Serializer\Expose()
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="ShiftAssignment", mappedBy="absence")
     */
    private $assignment;

    /**
     * @ORM\Column(type="string", name="reason", length=2048)
     */
    private $reason;

    /**
     * @ORM\ManyToOne(targetEntity="AbsenceStatus")
     * @ORM\JoinColumn(name="absence_status_id", referencedColumnName="id")
     */
    private $status;

    /**
     * @ORM\OneToOne(targetEntity="ShiftAssignment")
     * @ORM\JoinColumn(name="substitute_shift_assignment_id", referencedColumnName="id")
     *
     * @Serializer\Expose()
     */
    private $substitute;

    public function __construct(ShiftAssignment $assignment, string $reason) {
        $this->assignment = $assignment;
        $this->reason = $reason;

        $this->assignment->setAbsence($this);
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
     * Set reason
     *
     * @param string $reason
     *
     * @return Absence
     */
    public function setReason($reason) {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason() {
        return $this->reason;
    }

    /**
     * Set assignment
     *
     * @param \App\Entity\Schedule\ShiftAssignment $assignment
     *
     * @return Absence
     */
    public function setAssignment(\App\Entity\Schedule\ShiftAssignment $assignment = null) {
        $this->assignment = $assignment;

        return $this;
    }

    /**
     * Get assignment
     *
     * @return \App\Entity\Schedule\ShiftAssignment
     */
    public function getAssignment() {
        return $this->assignment;
    }

    /**
     * Set status
     *
     * @param \App\Entity\Schedule\AbsenceStatus $status
     *
     * @return Absence
     */
    public function setStatus(\App\Entity\Schedule\AbsenceStatus $status = null) {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return \App\Entity\Schedule\AbsenceStatus
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Set substitute
     *
     * @param \App\Entity\Schedule\ShiftAssignment $substitute
     *
     * @return Absence
     */
    public function setSubstitute(\App\Entity\Schedule\ShiftAssignment $substitute = null) {
        $this->substitute = $substitute;

        return $this;
    }

    /**
     * Get substitute
     *
     * @return \App\Entity\Schedule\ShiftAssignment
     */
    public function getSubstitute() {
        return $this->substitute;
    }
}
