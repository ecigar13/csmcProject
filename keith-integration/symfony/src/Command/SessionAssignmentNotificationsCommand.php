<?php


namespace App\Command;


use App\Utils\SessionAssignmentsNotifier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SessionAssignmentNotificationsCommand extends Command
{
    /**
     * @var SessionAssignmentsNotifier
     */
    private $notifier;

    public function __construct(SessionAssignmentsNotifier $notifier)
    {
        parent::__construct();
        $this->notifier = $notifier;
    }

    protected function configure()
    {
        $this
            ->setName('app:send-session-assignment-notifications')
            ->setDescription('Sends session assignment notifications for the current date according to 
            notification preferences of the mentors')
            ->setHelp('This command should be run every day towards the end of the day, when no more assignments 
            are likely to be performed');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->notifier->sendNotifications();
    }

}