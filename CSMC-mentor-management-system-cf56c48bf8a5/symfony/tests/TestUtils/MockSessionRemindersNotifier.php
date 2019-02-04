<?php


namespace App\Tests\TestUtils;


use App\Entity\User\User;
use App\Tests\Utils\SessionRemindersNotifierTest;
use App\Utils\SessionRemindersNotifier;

/**
 * *Important:* This class is used to indirectly test @see SessionRemindersNotifier . The method
 * @see SessionRemindersNotifier::sendEmailNotification is overidden to store the information instead of sending an
 * email. @see SessionRemindersNotifierTest
 *
 * @package App\Tests\TestUtils
 */
class MockSessionRemindersNotifier extends SessionRemindersNotifier
{
    /**
     * Stores the information that would have been used to send an email instead of sending it.
     *
     * @var array
     */
    private $sentEmailInfo = array();

    /**
     * Don't send email, just store the information.
     *
     * @param User $mentor
     * @param array $assignments
     * @param \DateTime $date
     */
    protected function sendEmailNotification(User $mentor, array $assignments, \DateTime $date)
    {
        $this->sentEmailInfo[] = array($mentor, $assignments, $date);
    }

    /**
     * @return array
     */
    public function getSentEmailInfo(): array
    {
        return $this->sentEmailInfo;
    }

}