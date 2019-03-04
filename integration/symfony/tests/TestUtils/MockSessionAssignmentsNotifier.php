<?php


namespace App\Tests\TestUtils;


use App\Entity\User\User;
use App\Tests\Utils\SessionAssignmentsNotifierTest;
use App\Utils\SessionAssignmentsNotifier;

/**
 * *Important:* This class is used to indirectly test @see SessionAssignmentsNotifier . The method
 * @see SessionRemindersNotifier::sendEmailNotification is overidden to store the information instead of sending an
 * email. @see SessionAssignmentsNotifierTest
 *
 * @package App\Tests\TestUtils
 */
class MockSessionAssignmentsNotifier extends SessionAssignmentsNotifier
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
     */
    protected function sendEmailNotification(User $mentor, array $assignments)
    {
        $this->sentEmailInfo[] = array($mentor, $assignments, new \DateTime());
    }

    /**
     * @return array
     */
    public function getSentEmailInfo(): array
    {
        return $this->sentEmailInfo;
    }

}