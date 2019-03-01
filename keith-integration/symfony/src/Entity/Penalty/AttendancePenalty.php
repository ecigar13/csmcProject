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
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="disc", type="string")
 */
abstract class AttendancePenalty
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     * @var string
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=false)
     * @var float
     */
    private $penaltyAmount;

    public function __construct(float $penaltyAmount)
    {
        $this->penaltyAmount = $penaltyAmount;
    }

    /**
     * @return float
     */
    public function getPenaltyAmount(): float
    {
        return $this->penaltyAmount;
    }

}