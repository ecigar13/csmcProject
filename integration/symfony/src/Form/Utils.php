<?php
/**
 * Created by PhpStorm.
 * User: juan
 * Date: 9/14/18
 * Time: 11:26 PM
 */

namespace App\Form;


class Utils
{
    /**
     * Turns a numeric array into an associative array where the keys are the string values of the numeric array values.
     * To be used with ChoiceType fields.
     *
     * @param array $values
     * @return array
     */
    public static function createOptionsArray(array $values)
    {
        $stringValues = array_map('strval', $values);

        return array_combine($stringValues, $values);
    }

}