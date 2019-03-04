<?php


namespace App\Utils\FakeEntities;


/**
 * These classes are used to quickly create domain objects without having to create all the required dependencies.
 * See for example @see \App\Utils\SessionRemindersNotifier::sendTestNotifications .
 */
class FakeShiftAssignment
{
    public $startTime;
    public $endTime;

    public function __construct($startTime, $endTime)
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * Return the same object so that we don't need additional fake objects for calls like getScheduledShift->getShift.
     *
     * @return $this
     */
    public function getScheduledShift()
    {
        return $this;
    }

    /**
     * Return the same object so that we don't need additional fake objects for calls like getScheduledShift->getShift.
     *
     * @return $this
     */
    public function getShift()
    {
        return $this;
    }

    public function getDate()
    {
        return new \DateTime('tomorrow');
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->endTime;
    }
}