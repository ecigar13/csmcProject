<?php

namespace App\Command;

use App\Entity\Occurrence\AbsenceOccurrence;

use App\Entity\Occurrence\ClaimShiftOccurrence;
use App\Entity\Occurrence\ShiftCoveredOccurrence;
use App\Entity\Schedule\ShiftAssignment;
use App\Entity\Schedule\Timesheet;
use App\Utils\AttendancePenaltyPersistenceManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class DetectAbsencesCommand extends Command
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
            ->setName("app:detect-absences")
            ->setDescription("Detects mentor absences and assigns penalty points.")
            ->setHelp(<<<EOT
This command allows you to detect mentor absences and assign penalty points, as
configured in the database. If executed without any specified date, the command
performs absence detection and penalty assignment for the current date.
EOT
)
            ->addArgument('date', InputArgument::OPTIONAL, '(optional) The date to detect absences for');
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

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                "This will recalculate all absence penalty assignments for "
                . $date->format('m/d/Y')
                . ", but will not delete existing absence penalty assignments for this date. "
                . "Do you wish to proceed? (Y/n) "
                , true);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }

            $this->detectAbsencesForDate($date);
            $this->entityManager->flush();
            if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_DEBUG)
                $this->io->success("Finished detecting absences for " . $date->format('m/d/Y'));
        } catch (\Exception $exception) {
            $this->io->error($exception->getMessage());
        }
    }

    /**
     * Compares each shift assignment to the timesheets of the assigned mentor.
     * Deems the mentor absent for his shift assignment if he did not sign in at
     * any time during the shift, and also has no timesheet that spans the start
     * time of the shift, i.e. if this situation is never found:
     *
     * <----|---------------------|--------------------|---->
     *  time in             shift start            time out
     *
     * NOTE: This command only logs absences if the mentor did not sign in at
     * all for a shift. The absences for if a mentor signs in super late to their
     * shift, but still during the shift, are handled at swipe time.
     *
     * @param \DateTime $date
     */
    private function detectAbsencesForDate(\DateTime $date) {
        /** @var ShiftAssignment[] $shiftAssignmentsForDate */
        $shiftAssignmentsForDate = $this->entityManager
            ->getRepository(ShiftAssignment::class)
            ->findForDate($date);

        foreach ($shiftAssignmentsForDate as $assignment) {
            $mentor = $assignment->getMentor();
            /** @var Timesheet[] $mentorTimesheetsForDate */
            $mentorTimesheetsForDate = $this->entityManager
                ->getRepository(Timesheet::class)
                ->findByUserAndDay($mentor, $date);
            $shift = $assignment->getScheduledShift()->getShift();
            $shiftStart = new \DateTime($shift->getStartTime()->format('H:i:s'));
            $shiftEnd = new \DateTime($shift->getEndTime()->format('H:i:s'));

            // Signed in anytime during shift
            $foundIntersectingTimesheet = false;
            foreach ($mentorTimesheetsForDate as $mentorTimesheet) {
                $timeIn = new \DateTime($mentorTimesheet->getTimeIn()->format('H:i:s'));
                if ($timeIn > $shiftStart && $timeIn < $shiftEnd) {
                    $foundIntersectingTimesheet = true;
                }
            }

            // Signed in before shift start time, and signed out after shift start time
            $foundSpanningTimesheet = false;
            foreach ($mentorTimesheetsForDate as $mentorTimesheet) {
                $timeIn = new \DateTime($mentorTimesheet->getTimeIn()->format('H:i:s'));
                $timeOut = $mentorTimesheet->getTimeOut();

                // NOTE: $timeOut could be null if mentor did not sign out.
                // In that event, treat just before midnight as sign out time.
                // Since this command is meant to be run at end of day, this should be OK.
                if ($timeOut == null) {
                    $timeOut = new \DateTime('23:59:00');
                }

                $timeOut = new \DateTime($timeOut->format('H:i:s'));

                if ($timeIn <= $shiftStart && $timeOut >= $shiftStart) {
                    $foundSpanningTimesheet = true;
                }
            }

            if (!$foundSpanningTimesheet && !$foundIntersectingTimesheet) {
                $penaltyAmount = $this->findAbsencePenalty($assignment);

                if ($penaltyAmount !== null) {
                    $shiftDateTime = $assignment->getAssignmentDateTime();
                    $hoursNotice = $assignment->getAbsenceNoticeAmountInHours();
                    $absenceOccurrence = new AbsenceOccurrence($mentor, $shiftDateTime, $hoursNotice, $penaltyAmount);
                    $this->entityManager->persist($absenceOccurrence);

                    if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_DEBUG) {
                        $this->output->write("Absence: ");
                        $this->printAssignmentInfo($assignment);
                        $this->output->writeln($penaltyAmount . " pts");
                    }
                }
            }

            $this->createShiftCoveredBonuses($assignment);
        }
    }

    /**
     * Checks when absence notice was given for this shift assignment
     *
     * @param ShiftAssignment $assignment
     * @return float|null
     */
    private function findAbsencePenalty(ShiftAssignment $assignment) {
        $absenceNotice = $assignment->getAbsence();

        $penalty = null;

        if ($absenceNotice == null) {
            $penalty = $this->penaltyManager->getAbsenceWithoutNoticePenalty();
        } else {
            $penalty = AttendancePenaltyPersistenceManager::findPenaltyForNoticeAmount($absenceNotice, $assignment, $this->entityManager);
        }

        if ($penalty) {
            $penaltyAmount = $penalty->getPenaltyAmount();
            return $penaltyAmount;
        } else {
            // FIXME: What do we do here?
            // No applicable absence penalty has been initialized yet
            if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_DEBUG)
                $this->io->text("No penalty found in database for this absence!");
        }

        return null;
    }

    private function createShiftCoveredBonuses(ShiftAssignment $assignment) {
        $originalMentor = $assignment->getMentor();
        $absenceNotice = $assignment->getAbsence();
        if ($absenceNotice) {
            $substituteAssignment = $absenceNotice->getSubstitute();
            if ($substituteAssignment) {
                $penaltyManager = AttendancePenaltyPersistenceManager::loadModel($this->entityManager);
                $shiftDateTime = $assignment->getAssignmentDateTime();
                $substituteMentor = $substituteAssignment->getMentor();
                $claimShiftBonus = $penaltyManager->getClaimShiftBonus();

                // create shift covered bonus for original mentor
                $shiftCoveredBonus = $penaltyManager->getShiftCoveredBonus();
                if ($shiftCoveredBonus) {
                    $points = $shiftCoveredBonus->getPenaltyAmount();
                    $shiftCoveredOccurrence = new ShiftCoveredOccurrence($originalMentor, $shiftDateTime, $substituteMentor, $points);
                    $this->entityManager->persist($shiftCoveredOccurrence);
                } else {
                    $this->io->text("No bonus found in database for getting shift covered!");
                }

                // create claim shift bonus for substitute mentor
                if ($claimShiftBonus) {
                    $points = $claimShiftBonus->getPenaltyAmount();
                    $claimShiftOccurrence = new ClaimShiftOccurrence($substituteMentor, $shiftDateTime, $originalMentor, $points);
                    $this->entityManager->persist($claimShiftOccurrence);
                } else {
                    $this->io->text("No bonus found in database for claiming shifts!");
                }
            }
        }
    }

    private function printAssignmentInfo(ShiftAssignment $assignment)
    {
        $info = "";
        $info .= $assignment->getScheduledShift()->getDate()->format('m/d/Y') . " ";
        $info .= $assignment->getMentor()->getUsername() . " ";
        $info .= $assignment->getScheduledShift()->getShift()->getStartTime()->format('H:i:s') . " ";
        $this->output->write($info);
    }

}