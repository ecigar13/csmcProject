<?php


namespace App\DBAL\Types;


use App\Entity\Occurrence\Occurrence;

class OccurrenceStatusType extends EnumType
{
    protected $name = 'occurrence_status';
    protected $values = array(Occurrence::STATUS_PENDING, Occurrence::STATUS_APPROVED, Occurrence::STATUS_REJECTED);

}