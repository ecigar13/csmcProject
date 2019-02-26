<?php

namespace App\Utils\FakeEntities;

use App\Entity\User\Info\NotificationPreferences;
use App\Entity\User\User;

/**
 * These classes are used to quickly create domain objects without having to create all the required dependencies.
 * See for example @see \App\Utils\SessionRemindersNotifier::sendTestNotifications .
 */
class FakeMentor extends User
{
    public $preferredName;
    public $address;

    public function __construct($preferredName, $address)
    {
        parent::__construct('Name', 'Last', 'mxm000000');
        $this->preferredName = $preferredName;
        $this->address = $address;
    }

    public function getPreferredName()
    {
        return $this->preferredName;
    }

    public function getNotificationPreferences(): NotificationPreferences
    {
        return new FakeNotificationPreferences($this, $this->address);
    }
}