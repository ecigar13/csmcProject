<?php


namespace App\DBAL\Types;


use App\DataType\GraduationSemester;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class GraduationSemesterType extends Type
{

    const GRADUATION_SEMESTER_TYPE = 'graduation_semester';

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!is_null($value)) {
            return sprintf('%s-%d', $value->getSeason(), $value->getYear());
        } else {
            return null;
        }
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (is_null($value)) {
            return null;
        }

        list($season, $year) = explode("-", $value);

        return GraduationSemester::createFromArray(array('season' => $season, 'year' => $year));
    }

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $fieldDeclaration The field declaration.
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform The currently used database platform.
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL(array(
            'length' => 15
        ));
    }

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return self::GRADUATION_SEMESTER_TYPE;
    }
}