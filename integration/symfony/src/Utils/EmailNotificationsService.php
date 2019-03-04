<?php


namespace App\Utils;

use Swift_Mailer as Mailer;
use Swift_Message as Message;
use Swift_SmtpTransport as SmtpTransport;


class EmailNotificationsService
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var string
     */
    private $fromEmail;

    /**
     * @param string $smtpUsername
     * @param string $smtpPassword
     * @param string $fromEmail
     */
    public function __construct(string $smtpUsername, string $smtpPassword, string $fromEmail)
    {
        $transport = (new SmtpTransport('smtpauth.utdallas.edu', 587))
            ->setUsername($smtpUsername)
            ->setPassword($smtpPassword);

        $this->mailer = new Mailer($transport);
        $this->fromEmail = $fromEmail;
    }

    /**
     * Sends the email to the corresponding address.
     *
     * @param string $emailAddress
     * @param string $body
     * @param string $subject
     * @param bool $isHTML
     * @return int The result of the email send call
     */
    public function sendEmail(string $emailAddress, string $body, string $subject = null, $isHTML = true)
    {
        $message = (new Message($subject))
            ->setFrom([$this->fromEmail => 'CSMC'])
            ->setTo([$emailAddress])
            ->setBody($body);

        if ($isHTML) {
            $message->setContentType('text/html');
        }

        return $this->mailer->send($message);
    }
}