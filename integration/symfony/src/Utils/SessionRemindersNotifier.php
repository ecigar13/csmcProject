<?php


namespace App\Utils;


use App\Entity\Schedule\ShiftAssignment;
use App\Entity\User\User;
use App\Tests\TestUtils\MockSessionRemindersNotifier;
use App\Tests\Utils\SessionRemindersNotifierTest;
use App\Utils\FakeEntities\FakeMentor;
use App\Utils\FakeEntities\FakeShiftAssignment;
use Doctrine\ORM\EntityManagerInterface;
use function Deployer\Support\str_contains;

/**
 * *Important:* Before modifying this class make sure you understand how it's tested. The method
 * @see SessionRemindersNotifier::sendEmailNotification is overidden in
 * @see MockSessionRemindersNotifier to collect the data passed to it
 * instead of sending an email. Then that class is tested instead of this one.
 * @see SessionRemindersNotifierTest
 *
 * @package App\Utils
 */
class SessionRemindersNotifier
{
    /**
     * @var EmailNotificationsService
     */
    private $emailService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(EmailNotificationsService $emailService, \Twig_Environment $twig, EntityManagerInterface $entityManager)
    {
        $this->emailService = $emailService;
        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sendNotifications()
    {
        $candidateMentors = $this->entityManager->getRepository(User::class)
            ->findMentorsWithEnabledSessionReminders();

        foreach ($candidateMentors as $mentor) {
            $notificationPreferences = $mentor->getNotificationPreferences();

            if (!$notificationPreferences->isUseEmail() && !$notificationPreferences->isUsePhoneNumber()) {
                // If neither is enabled, there is nothing to do for this mentor
                continue;
            }

            $notificationDays = $notificationPreferences->getSessionReminderAdvanceDays();
            $notificationDate = new \DateTime("+$notificationDays days");

            $assignments = $this->entityManager->getRepository(ShiftAssignment::class)
                ->findSessionsForMentorAndDate($mentor, $notificationDate);

            if ($assignments != null) {
                if ($notificationPreferences->isUseEmail()) {
                    $this->sendEmailNotification($mentor, $assignments, $notificationDate);
                }

                if ($notificationPreferences->isUsePhoneNumber()) {
                    $this->sendTextNotification($mentor, $assignments, $notificationDate);
                }
            }
        }
    }

    /**
     * @param User $mentor
     * @param array $assignments
     * @param \DateTime $date
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function sendEmailNotification(User $mentor, array $assignments, \DateTime $date)
    {
        $emailAddress = $mentor->getNotificationPreferences()->getPreferredEmail();
        $subject = sprintf('CSMC Session Reminder for %s', $date->format('m/d/Y'));
        $body = $this->twig->render('shared/notifications/session_reminder.html.twig', array(
            'mentor' => $mentor,
            'assignments' => $assignments,
            'assignmentDate' => $date
        ));

        $this->emailService->sendEmail($emailAddress, $body, $subject);
    }

    /**
     * @param User $mentor
     * @param ShiftAssignment[] $assignments
     * @param \DateTime $notificationDate
     */
    protected function sendTextNotification(User $mentor, array $assignments, \DateTime $notificationDate)
    {
        // TODO: write tests for this
        $preferences = $mentor->getNotificationPreferences();

        $carrier = $preferences->getPreferredPhoneNumberCarrier();
        $gatewayAddresses = SMSEmailGateway::CARRIER_EMAIL_GATEWAY_ADDRESSES;
        if (!isset($gatewayAddresses[$carrier])) {
            return;
        }

        $emailAddress = $preferences->getPreferredPhoneNumber() . "@" . $gatewayAddresses[$carrier];

        $formattedDate = $notificationDate->format('m/d/Y');
        $message = "CSMC Sess. Reminder $formattedDate:";

        foreach ($assignments as $assignment) {
            $shift = $assignment->getScheduledShift()->getShift();
            $start = $shift->getStartTime()->format('H:II');
            $end = $shift->getEndTime()->format('H:II');
            $message .= "\n$start-$end";
        }

        $this->emailService->sendEmail($emailAddress, $message, null, false);
    }

    /**
     * Sends notifications with fake assignments to the specified phone numbers or email addresses. Make sure changes in
     * @see sendEmailNotification and the template are reflected in the fake objects here.
     *
     * @param string[] $addresses
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sendTestNotifications(array $addresses)
    {
        foreach ($addresses as $address) {
            $mentor = new FakeMentor($address, $address);
            $fakeAssignments = array();

            $startHour = 7;
            $total = rand(1, 5);
            for ($i = 0; $i < $total; $i++) {
                $endHour = $startHour + 1;
                $fakeAssignments[] = new FakeShiftAssignment(new \DateTime("$startHour:00"), new \DateTime("$endHour:00"));
                $startHour = $endHour + 1;
            }

            $notificationDate = new \DateTime('tomorrow');

            if (!str_contains($address, ":")) {
                $this->sendEmailNotification($mentor, $fakeAssignments, $notificationDate);
            } else {
                $this->sendTextNotification($mentor, $fakeAssignments, $notificationDate);
            }
        }
    }

}
