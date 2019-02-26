<?php


namespace App\Entity\Occurrence;


use App\Entity\User\User;
use App\Utils\DateTimeService;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 *
 * @package App\Entity\Occurrence
 */
class ShiftCoveredOccurrence extends AttendanceOccurrence
{
    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $shiftDateTime;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\User\User")
     * @ORM\JoinColumn(name="covered_by", referencedColumnName="id")
     */
    private $coveredBy;

    public function __construct(User $subject, \DateTime $shiftDateTime, User $coveredBy, float $points = null,
                                string $adminNotes = null, DateTimeService $time = null)
    {
        parent::__construct($subject, $points, $adminNotes, $time);
        $this->shiftDateTime = $shiftDateTime;
        $this->coveredBy = $coveredBy;
    }

    /**
     * @return \DateTime
     */
    public function getShiftDateTime()
    {
        return $this->shiftDateTime;
    }

    /**
     * @return User
     */
    public function getCoveredBy()
    {
        return $this->coveredBy;
    }
}