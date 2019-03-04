<?php

namespace App\Utils\FakeEntities;

use App\Entity\User\Info\NotificationPreferences;
use function Deployer\Support\str_contains;

/**
 * These classes are used to quickly create domain objects without having to create all the required dependencies.
 * See for example @see \App\Utils\SessionRemindersNotifier::sendTestNotifications .
 */
class FakeNotificationPreferences extends NotificationPreferences
{
    public $email;
    public $phone;
    public $carrier;

    public function __construct($user, $address)
    {
        parent::__construct($user);
        if (!str_contains($address, ":")) {
            $this->email = $address;
        }else{
            list($this->phone, $this->carrier) = explode(":", $address);
        }
    }

    public function getPreferredEmail()
    {
        return $this->email;
    }

    public function getPreferredPhoneNumber()
    {
        return $this->phone;
    }

    public function getPreferredPhoneNumberCarrier()
    {
        return $this->carrier;
    }
}