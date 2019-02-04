<?php


namespace App\DBAL\Types;


use Doctrine\DBAL\Platforms\AbstractPlatform;

class NoEnumRequestStatusType extends RequestStatusType
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL(array(
            'length' => 20
        ));
    }

}