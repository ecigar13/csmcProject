<?php


namespace App\Tests\TestUtils;


use App\Utils\DateTimeService;

class MockDateTimeService extends DateTimeService
{
    /**
     * @var string
     */
    public static $now = null;

    /**
     * If the @see MockDateTimeService::$now property is set, returns an object corresponding to that date, otherwise
     * returns the actual current time.
     *
     * @return \DateTime
     */
    public function now()
    {
        if (self::$now != null) {
            return new \DateTime(self::$now);
        } else {
            return new \DateTime();
        }
    }

}