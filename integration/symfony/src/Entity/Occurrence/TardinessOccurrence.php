<?php


namespace App\Entity\Occurrence;


use App\Entity\User\User;
use App\Utils\DateTimeService;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Occurrence\TardinessOccurrenceRepository")
 * @package App\Entity\Occurrence
 */
class TardinessOccurrence extends AttendanceOccurrence
{
    /**
     * @ORM\Column(type="smallint")
     *
     * @var int
     */
    private $tardinessMinutes;

    /**
     * It will be non-null if this occurrence is part of a cumulative occurrence.
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Occurrence\CumulativeTardinessOccurrence", inversedBy="accumulatedOccurrences", cascade={"persist"})
     *
     * @var CumulativeTardinessOccurrence|null
     */
    private $cumulativeOccurrence;

    /**
     * @param User $subject
     * @param float $points
     * @param int $tardinessMinutes
     * @param DateTimeService|null $time
     */
    public function __construct(User $subject, float $points, int $tardinessMinutes, DateTimeService $time = null)
    {
        parent::__construct($subject, $points, null, $time);

        $this->tardinessMinutes = $tardinessMinutes;
    }

    /**
     * @return int
     */
    public function getTardinessMinutes(): int
    {
        return $this->tardinessMinutes;
    }

    /**
     * @return CumulativeTardinessOccurrence|null
     */
    public function getCumulativeOccurrence()
    {
        return $this->cumulativeOccurrence;
    }

    // FIXME: Breaks encapsulation, but association needs to be made from the owning side to be persisted
    /**
     * @param CumulativeTardinessOccurrence $cumulativeOccurrence
     */
    public function setCumulativeOccurrence($cumulativeOccurrence) {
        $this->cumulativeOccurrence = $cumulativeOccurrence;
    }

}