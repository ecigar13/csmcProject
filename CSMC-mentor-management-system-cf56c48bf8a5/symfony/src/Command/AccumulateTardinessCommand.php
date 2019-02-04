<?php

namespace App\Command;


use App\Entity\Occurrence\CumulativeTardinessOccurrence;
use App\Entity\Occurrence\TardinessOccurrence;
use App\Entity\User\User;
use App\Utils\AttendancePenaltyPersistenceManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class AccumulateTardinessCommand extends Command
{
    private $entityManager;
    private $penaltyManager;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var OutputInterface
     */
    private $output;


    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->penaltyManager = AttendancePenaltyPersistenceManager::loadModel($this->entityManager);
        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setName("app:accumulate-tardiness")
            ->setDescription("Totals each mentor's cumulative tardiness for the week")
            ->setHelp(<<<EOT
Totals each mentor's cumulative tardiness for the week (Sunday - Saturday).
Cumulative tardiness is totaled for tardies that are within the first tardiness
threshold (e.g. within 5 minutes) if that threshold is marked cumulative by
admin. If the first tardiness threshold is not marked cumulative, and is
instead assigned a point value, then this command will not total the tardiness.

This command is recommended to be run on Saturdays after end-of-day but before
Sunday begins. However, the command can also be run for a specific week if
desired. To do so, enter a date as an argument and the command will be run for
the week (Sunday - Saturday) containing that date.
EOT
            )
            ->addArgument('date', InputArgument::OPTIONAL, '(optional) Cumulative tardiness will be totaled for the week containing this date');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->output = $output;

        $dateString = $input->getArgument('date');
        $dateString = trim($dateString);

        try {
            if ($dateString) {
                $date = \DateTime::createFromFormat('Y-m-d', $dateString);
                if (!$date || $dateString != $date->format('Y-m-d'))
                    throw new \Exception("Invalid date. Expected format: yyyy-mm-dd");
            } else {
                $date = new \DateTime();
            }

            $week = $this->getWeekStartAndEnd($date);

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                "This will recalculate all tardiness accumulation for the week of "
                . $week['sun']->format('m/d/Y') . " - " . $week['sat']->format('m/d/Y')
                . ", but will not delete existing tardiness accumulations for this week, "
                . "nor double count them if they have already been accumulated. "
                . "Do you wish to proceed? (Y/n) "
                , true);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }

            $this->totalCumulativeTardiness($week['sun'], $week['sat']);
            $this->entityManager->flush();
            if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_DEBUG) {
                $this->io->success("Finished accumulating tardiness for the week of "
                . $week['sun']->format('m/d/Y') . " - " . $week['sat']->format('m/d/Y'));
            }
        } catch (\Exception $exception) {
            $this->io->error($exception->getMessage());
        }
    }

    /**
     * Finds the Sunday that the week starts on and the Saturday that the week ends on
     *
     * @param \DateTime $date
     * @return array
     */
    private function getWeekStartAndEnd(\DateTime $date) {
        $SUNDAY = 0;
        $SATURDAY = 6;

        $weekDay = $date->format('w');
        $weekSunday = null;
        $weekSaturday = null;

        // Handle Sunday and Saturdays separately to make them inclusive
        if ($weekDay == $SUNDAY) {
            $weekSunday = $date;
            $dateCopy = new \DateTime($date->format('m/d/Y'));
            $weekSaturday = $dateCopy->modify('next Saturday');
        } else if ($weekDay == $SATURDAY) {
            $dateCopy = new \DateTime($date->format('m/d/Y'));
            $weekSunday = $dateCopy->modify('last Sunday');
            $weekSaturday = $date;
        } else {
            $dateCopy = new \DateTime($date->format('m/d/Y'));
            $weekSunday = $dateCopy->modify('last Sunday');
            $dateCopy = new \DateTime($date->format('m/d/Y'));
            $weekSaturday = $dateCopy->modify('next Saturday');
        }

        // set end time on Saturday to 11:59 PM
        $weekSaturday = new \DateTime($weekSaturday->format('y-m-d') . ' 11:59 PM');

        return array(
            'sun' => $weekSunday,
            'sat' => $weekSaturday
        );
    }

    private function totalCumulativeTardiness(\DateTime $weekStart, \DateTime $weekEnd) {
        $tardinessPenalties = $this->penaltyManager->getTardinessPenalties();
        if (empty($tardinessPenalties)) return;
        if (!$tardinessPenalties[0]->isCumulative()) return;

        $cumulativeThreshold = $tardinessPenalties[0]->getEndingMinutes();

        $mentors = $this->entityManager
            ->getRepository(User::class)
            ->findByRole('mentor');

        foreach ($mentors as $mentor) {
            $mentorTardies = $this->entityManager
                ->getRepository(TardinessOccurrence::class)
                ->findForMentorBetweenDatesWithinAmount($mentor, $weekStart, $weekEnd, $cumulativeThreshold);

            // Filter out tardiness occurrences that have already been assigned to a cumulative one
            $mentorTardies = array_filter($mentorTardies, function($tardy) {
               return !$tardy->getCumulativeOccurrence();
            });

            $cumulativeTardiness = array_reduce($mentorTardies, function($carry, $tardy) {
                $carry += $tardy->getTardinessMinutes();
                return $carry;
            });

            if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_DEBUG)
                $this->output->writeln($mentor->getUsername() . ' ' . $cumulativeTardiness . ' minutes');

            if ($cumulativeTardiness > 0) {
                $occurrence = new CumulativeTardinessOccurrence($mentor, 0, $cumulativeTardiness, $weekStart, $weekEnd, $mentorTardies);
                $this->entityManager->persist($occurrence);
            }
        }
    }

}