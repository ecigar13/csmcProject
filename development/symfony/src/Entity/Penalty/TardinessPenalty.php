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
class TardinessPenalty extends AttendancePenalty
{
    /**
     * Amount of minutes from the start of the session from when this penalty should be applied, inclusive.
     *
     * @ORM\Column(type="smallint", nullable=false)
     * @var int
     */
    private $startingMinutes;

    /**
     * Amount of minutes from the start of the session where this penalty should stop being applied, exclusive.
     *
     * @ORM\Column(type="smallint", nullable=false)
     * @var int
     */
    private $endingMinutes;

    /**
     * Whether occurrences of this penalty should be collected at the end of the week and treated as a single occurrence.
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @var bool
     */
    private $isCumulative;

    /**
     * TardinessPenalty constructor.
     * @param float $penaltyAmount
     * @param int $startingMinutes
     * @param int $endingMinutes
     * @param bool $isCumulative
     */
    public function __construct(float $penaltyAmount, int $startingMinutes, int $endingMinutes, bool $isCumulative)
    {
        parent::__construct($penaltyAmount);

        $this->startingMinutes = $startingMinutes;
        $this->endingMinutes = $endingMinutes;
        $this->isCumulative = $isCumulative;
    }

    /**
     * @return int
     */
    public function getStartingMinutes()
    {
        return $this->startingMinutes;
    }


    /**
     * @return int
     */
    public function getEndingMinutes(): int
    {
        return $this->endingMinutes;
    }

    /**
     * @return bool
     */
    public function isCumulative(): bool
    {
        return $this->isCumulative;
    }

}