<?php


namespace App\Entity\Penalty;


use Doctrine\ORM\Mapping as ORM;

/**
 * This class stores the bonus a mentor gets for getting their shift covered after giving notice of absence.
 *
 * **Important Note:** Do *NOT* instantiate/modify this class directly! Use an instance of the
 * @see AttendancePenaltyPersistenceManager instead.
 *
 * @package App\Entity\Penalty
 * @internal see notice
 * @ORM\Entity
 */
class ShiftCoveredBonus extends AttendancePenalty
{

}