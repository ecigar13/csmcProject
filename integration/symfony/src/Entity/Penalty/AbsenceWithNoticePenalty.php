<?php


namespace App\Entity\Penalty;


use Doctrine\ORM\Mapping as ORM;

/**
 * **Important Note:** Do *NOT* instantiate/modify this class directly! Use an instance of the
 * @see AttendancePenaltyPersistenceManager instead.
 *
 * @package App\Entity\Penalty
 * @internal see notice
 * @ORM\Entity
 */
class AbsenceWithNoticePenalty extends AttendancePenalty
{
    /**
     * Amount of hours before the session from where this penalty should be applied, inclusive.
     * A value of `null` means it should be applied if no later penalties apply.
     *
     * @ORM\Column(type="smallint", nullable=true)
     * @var int|null
     */
    private $hoursBefore;

    /**
     * Whether or not the absence is justified.
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @var bool
     */
    private $isJustified;

    /**
     * AbsenceWithNoticePenalty constructor.
     * @param float $penaltyAmount
     * @param bool $isJustified
     * @param int|null $hoursBefore
     */
    public function __construct(float $penaltyAmount, bool $isJustified, int $hoursBefore = null)
    {
        parent::__construct($penaltyAmount);

        $this->hoursBefore = $hoursBefore;
        $this->isJustified = $isJustified;
    }

    /**
     * @return int|null
     */
    public function getHoursBefore()
    {
        return $this->hoursBefore;
    }

}