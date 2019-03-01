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
class AbsenceOccurrence extends AttendanceOccurrence
{
    // 0 hours notice means 0-1 hour notice (e.g. 5 minutes notice)
    // -1 hours notice means no notice is given
    const NO_NOTICE = -1;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $shiftDateTime;


    /**
     * @ORM\Column(type="integer")
     */
    private $hoursNotice;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    private $justified;

    public function __construct(User $subject, \DateTime $shiftDateTime, int $hoursNotice, float $points = null,
                                string $adminNotes = null, DateTimeService $time = null)
    {
        parent::__construct($subject, $points, $adminNotes, $time);

        $this->shiftDateTime = $shiftDateTime;
        $this->hoursNotice = $hoursNotice;
        $this->justified = false;
    }

    /**
     * @return \DateTime
     */
    public function getShiftDateTime()
    {
        return $this->shiftDateTime;
    }

    /**
     * @return int
     */
    public function getHoursNotice() {
        return $this->hoursNotice;
    }

    /**
     * @return bool
     */
    public function isJustified(): bool
    {
        return $this->justified;
    }

    /**
     * @param bool $justified
     */
    public function setJustified(bool $justified)
    {
        $this->justified = $justified;
    }

}