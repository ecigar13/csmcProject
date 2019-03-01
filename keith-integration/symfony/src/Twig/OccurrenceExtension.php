<?php


namespace App\Twig;


use App\Entity\Occurrence\AbsenceOccurrence;
use App\Entity\Occurrence\BehaviorOccurrence;
use App\Entity\Occurrence\ClaimShiftOccurrence;
use App\Entity\Occurrence\CumulativeTardinessOccurrence;
use App\Entity\Occurrence\Occurrence;
use App\Entity\Occurrence\ShiftCoveredOccurrence;
use App\Entity\Occurrence\TardinessOccurrence;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class OccurrenceExtension extends AbstractExtension
{
    public function getFilters()
    {
        return array(
            new TwigFilter('occurrenceSubmitter', array($this, 'submitter')),
            new TwigFilter('occurrenceFilter', array($this, 'filter')),
            new TwigFilter('occurrenceType', array($this, 'type')),
            new TwigFilter('occurrenceDescription', array($this, 'description')),
            new TwigFilter('occurrenceDate', array($this, 'date')),
            new TwigFilter('occurrencePoints', array($this, 'points')),
            new TwigFilter('occurrenceCanEditType', array($this, 'canEditType')),
            new TwigFilter('occurrenceEditClass', array($this, 'getEditClass'))
        );
    }

    public function getTests()
    {
        return array(
            new TwigTest('approved', array($this, 'isApproved'))
        );
    }

    /**
     * @param Occurrence $occurrence
     * @return string
     */
    public function submitter(Occurrence $occurrence)
    {
        if ($occurrence instanceof BehaviorOccurrence) {
            $submitter = $occurrence->getSubmitter();
            return $submitter != null ? $submitter->getPreferredName() : 'Anonymous';
        } else {
            return 'System';
        }
    }

    /**
     * @param Occurrence $occurrence
     * @return string
     */
    public function filter(Occurrence $occurrence)
    {
        if ($occurrence instanceof BehaviorOccurrence) {
            return 'BehaviorEvent';
        } elseif ($occurrence instanceof CumulativeTardinessOccurrence
            || $occurrence instanceof TardinessOccurrence
            || $occurrence instanceof AbsenceOccurrence
            || $occurrence instanceof ClaimShiftOccurrence
            || $occurrence instanceof ShiftCoveredOccurrence
        ) {
            return 'AttendanceEvent';
        }
        return '';
    }

    /**
     * @param Occurrence $occurrence
     * @return string
     */
    public function type(Occurrence $occurrence)
    {
        if ($occurrence instanceof BehaviorOccurrence) {
            return $occurrence->getType();
        } elseif ($occurrence instanceof CumulativeTardinessOccurrence) {
            return 'Tardiness (ACC)';
        } elseif ($occurrence instanceof TardinessOccurrence) {
            return 'Tardiness';
        } elseif ($occurrence instanceof AbsenceOccurrence) {
            if ($occurrence->isJustified())
                return 'Absence (Excused)';
            return 'Absence (Unexcused)';
        } elseif ($occurrence instanceof ClaimShiftOccurrence) {
            return 'Claim Shift';
        } else if ($occurrence instanceof ShiftCoveredOccurrence) {
            return 'Shift Covered';
        }

        return '';
    }

    /**
     * @param Occurrence $occurrence
     * @return string
     */
    public function description(Occurrence $occurrence)
    {
        if ($occurrence instanceof BehaviorOccurrence) {
            return $occurrence->getDetails();
        } elseif ($occurrence instanceof TardinessOccurrence) {
            $minutes = $occurrence->getTardinessMinutes();
            return "$minutes minutes";
        } elseif ($occurrence instanceof AbsenceOccurrence) {
            $hoursNotice = $occurrence->getHoursNotice();
            if ($hoursNotice == AbsenceOccurrence::NO_NOTICE)
                return 'No notice';
            else if ($hoursNotice == 0)
                return '< 1 hour notice';
            else if ($hoursNotice == 1)
                return '1 hour notice';
            else if ($hoursNotice <= 48)
                return $hoursNotice . ' hours notice';
            else
                return floor($hoursNotice / 24) . ' days notice';
        } elseif ($occurrence instanceof ClaimShiftOccurrence) {
            return "Covering for " . $occurrence->getCoveringFor()->getPreferredName();
        } elseif ($occurrence instanceof ShiftCoveredOccurrence) {
            return "Covered by " . $occurrence->getCoveredBy()->getPreferredName();
        }

        return '';
    }

    /**
     * @param Occurrence $occurrence
     * @return \DateTime
     */
    public function date(Occurrence $occurrence)
    {
        if ($occurrence instanceof BehaviorOccurrence) {
            return $occurrence->getReportedDate();
        } else if (
            $occurrence instanceof AbsenceOccurrence
            || $occurrence instanceof ClaimShiftOccurrence
            || $occurrence instanceof ShiftCoveredOccurrence
        ) {
            return $occurrence->getShiftDateTime();
        } else {
            return $occurrence->getCreationDate();
        }
    }

    /**
     * @param Occurrence $occurrence
     * @return string
     */
    public function points(Occurrence $occurrence)
    {
        return sprintf('%+.1f', $occurrence->getPoints());
    }

    /**
     * @param Occurrence $occurrence
     * @return bool
     */
    public function isApproved(Occurrence $occurrence)
    {
        return $occurrence->getStatus() == Occurrence::STATUS_APPROVED;
    }

    public function canEditType(Occurrence $occurrence) {
        if ($occurrence instanceof BehaviorOccurrence || $occurrence instanceof AbsenceOccurrence) {
            return true;
        }
        return false;
    }

    public function getEditClass(Occurrence $occurrence)
    {
        if ($occurrence instanceof BehaviorOccurrence) {
            return "occurrence-type-editable behavior-type-editable";
        } else if ($occurrence instanceof AbsenceOccurrence) {
            return "occurrence-type-editable absence-type-editable";
        }
        return "";
    }
}