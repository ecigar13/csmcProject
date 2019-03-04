<?php


namespace App\Command;

use App\Utils\SessionRemindersNotifier;
use App\Utils\SMSEmailGateway;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestSessionRemindersCommand extends Command
{
    /**
     * @var SessionRemindersNotifier
     */
    private $notifier;

    public function __construct(SessionRemindersNotifier $notifier)
    {
        parent::__construct();

        $this->notifier = $notifier;
    }

    protected function configure()
    {
        $carriers = implode(", ", array_keys(SMSEmailGateway::CARRIER_EMAIL_GATEWAY_ADDRESSES));

        $this->setName('app:test-session-reminders')
            ->setDescription('Sends test notifications to specified emails')
            ->addArgument('addresses', InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'List of email addresses or phone numbers in format "number:carrier" to send test notifications to')
            ->setHelp("Available carriers are: $carriers");
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
        $addresses = $input->getArgument('addresses');

        $this->notifier->sendTestNotifications($addresses);
    }
}