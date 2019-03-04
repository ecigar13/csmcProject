<?php


namespace App\Command;


use App\Utils\SessionRemindersNotifier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SessionRemindersCommand extends Command
{
    /**
     * @var SessionRemindersNotifier
     */
    private $notifier;

    /**
     * @param SessionRemindersNotifier $notifier
     */
    public function __construct(SessionRemindersNotifier $notifier)
    {
        parent::__construct();
        $this->notifier = $notifier;
    }

    protected function configure()
    {
        $this
            ->setName('app:send-session-reminders')
            ->setDescription("Sends session reminders according to mentors' notification preferences")
            ->setHelp('This command should be run every day at the beginning of the day');
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