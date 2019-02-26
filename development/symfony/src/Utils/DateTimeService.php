<?php


namespace App\Utils;


/**
 * Exists mostly so that we can easily mock the current time in tests.
 *
 * @package App\Utils
 */
class DateTimeService
{
    /**
     * @return \DateTime
     */
    public function now()
    {
        return new \DateTime();
    }

}