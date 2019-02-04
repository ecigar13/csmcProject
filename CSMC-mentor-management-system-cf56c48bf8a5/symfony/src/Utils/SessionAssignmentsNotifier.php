<?php


namespace App\Utils;


use App\Entity\Schedule\ShiftAssignment;
use App\Entity\User\User;
use App\Tests\TestUtils\MockSessionAssignmentsNotifier;
use App\Tests\Utils\SessionAssignmentsNotifierTest;
use App\Utils\FakeEntities\FakeMentor;
use App\Utils\FakeEntities\FakeShiftAssignment;
use function Deployer\Support\str_contains;
use Doctrine\ORM\EntityManagerInterface;

/**
 * *Important:* Before modifying this class make sure you understand how it's tested. The method
 * @see SessionAssignmentsNotifier::sendEmailNotification is overidden in
 * @see MockSessionAssignmentsNotifier to collect the data passed to it
 * instead of sending an email. Then that class is tested instead of this one.
 * @see SessionAssignmentsNotifierTest
 *
 * @package App\Utils
 */
class SessionAssignmentsNotifier
{
    /**
     * @var EmailNotificationsService
     */
    private $emailService;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EmailNotificationsService $email, \Twig_Environment $twig, EntityManagerInterface $entityManager)
    {
        $this->emailService = $email;
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
            ->findMentorsWithEnabledSessionAssignmentNotifications();

        foreach ($candidateMentors as $mentor) {
            $notificationPreferences = $mentor->getNotificationPreferences();

            if (!$notificationPreferences->isUseEmail() && !$notificationPreferences->isUsePhoneNumber()) {
                // If neither is enabled, there is nothing to do for this mentor
                continue;
            }

            $assignments = $this->entityManager->getRepository(ShiftAssignment::class)
                ->findSessionsForMentorAssignedToday($mentor);

            if ($assignments != null) {
                if ($notificationPreferences->isUseEmail()) {
                    $this->sendEmailNotification($mentor, $assignments);
                }

                if ($notificationPreferences->isUsePhoneNumber()) {
                    $this->sendTextNotification($mentor, $assignments);
                }
            }
        }
    }

    /**
     * @param User $mentor
     * @param ShiftAssignment[] $assignments
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function sendEmailNotification(User $mentor, array $assignments)
    {
        $date = new \DateTime();

        $emailAddress = $mentor->getNotificationPreferences()->getPreferredEmail();
        $subject = sprintf('CSMC Session Assignment Notification for %s', $date->format('m/d/Y'));
        $body = $this->twig->render('shared/notifications/assignment_notification.html.twig', array(
            'mentor' => $mentor,
            'assignments' => $assignments,
            'assignmentDate' => $date
        ));

        $this->emailService->sendEmail($emailAddress, $body, $subject);
    }

    /**
     * @param User $mentor
     * @param ShiftAssignment[] $assignments
     */
    protected function sendTextNotification(User $mentor, array $assignments)
    {
        // TODO: write tests for this
        $preferences = $mentor->getNotificationPreferences();

        $carrier = $preferences->getPreferredPhoneNumberCarrier();
        $gatewayAddresses = SMSEmailGateway::CARRIER_EMAIL_GATEWAY_ADDRESSES;
        if (!isset($gatewayAddresses[$carrier])) {
            return;
        }

        $emailAddress = $preferences->getPreferredPhoneNumber() . "@" . $gatewayAddresses[$carrier];

        $message = "CSMC Sess. Assign. Notif.:";

        foreach ($assignments as $assignment) {
            $shift = $assignment->getScheduledShift()->getShift();
            $start = $shift->getStartTime()->format('H:II');
            $end = $shift->getEndTime()->format('H:II');
            $date = $assignment->getScheduledShift()->getDate()->format('m/d/Y');
            $message .= "\n$date--$start-$end";
        }

        $this->emailService->sendEmail($emailAddress, $message, null, false);
    }

    /**
     * Sends notifications with fake assignments to the specified email addresses. Make sure changes in
     * @see sendEmailNotification and the template are reflected in the fake objects here.
     *
     * @param $addresses
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sendTestNotifications($addresses)
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

            if (!str_contains($address, ":")) {
                $this->sendEmailNotification($mentor, $fakeAssignments);
            } else {
                $this->sendTextNotification($mentor, $fakeAssignments);
            }
        }
    }

}