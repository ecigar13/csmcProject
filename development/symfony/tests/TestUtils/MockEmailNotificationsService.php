<?php


namespace App\Tests\TestUtils;


use App\Utils\EmailNotificationsService;

class MockEmailNotificationsService extends EmailNotificationsService
{
    public static $sentEmails = array();

    public function sendEmail(string $emailAddress, string $body, string $subject = null, $isHTML = true)
    {
        self::$sentEmails[] = [$emailAddress, $subject, $body];
    }

}