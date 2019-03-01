<?php


namespace App\Command;


use App\Utils\SessionAssignmentsNotifier;
use App\Utils\SMSEmailGateway;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestSessionAssignmentNotificationsCommand extends Command
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
        $carriers = implode(', ', SMSEmailGateway::CARRIER_EMAIL_GATEWAY_ADDRESSES);

        $this->setName('app:test-assignment-notifications')
            ->setDescription('Sends test notifications to specified emails')
            ->addArgument('emailAddresses', InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'List of email addresses or phone numbers in format "phone:carrier" to send test notifications to')
            ->setHelp("Available carriers are $carriers");
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
        $addresses = $input->getArgument('emailAddresses');

        $this->notifier->sendTestNotifications($addresses);
    }

}