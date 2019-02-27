<?php


namespace App\DBAL\Types;


use Doctrine\DBAL\Platforms\AbstractPlatform;

class NoEnumOccurrenceStatusType extends OccurrenceStatusType
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL(array(
            'length' => 20
        ));
    }

}