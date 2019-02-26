<?php


namespace App\Entity\Penalty;


use Doctrine\ORM\Mapping as ORM;

/**
 * This class stores the bonus a mentor gets for covering someone's shift after that person gives a notice of absence.
 *
 * **Important Note:** Do *NOT* instantiate/modify this class directly! Use an instance of the
 * @see AttendancePenaltyPersistenceManager instead.
 *
 * @internal see notice
 * @package App\Entity\Penalty
 * @ORM\Entity
 */
class ClaimShiftBonus extends AttendancePenalty
{

}