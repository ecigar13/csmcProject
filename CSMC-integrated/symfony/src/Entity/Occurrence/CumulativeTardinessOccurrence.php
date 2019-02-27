<?php


namespace App\Entity\Occurrence;


use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;

/**
 * Stores the details of a cumulative tardiness occurrence.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Occurrence\CumulativeTardinessOccurrenceRepository")
 *
 * @package App\Entity\Occurrence
 */
class CumulativeTardinessOccurrence extends TardinessOccurrence
{
    /**
     * @ORM\Column(type="date")
     *
     * @var \DateTime
     */
    private $periodStart;

    /**
     * @ORM\Column(type="date")
     *
     * @var \DateTime
     */
    private $periodEnd;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Occurrence\TardinessOccurrence", mappedBy="cumulativeOccurrence")
     *
     * @var TardinessOccurrence[]
     */
    private $accumulatedOccurrences;

    /**
     * @param User $subject
     * @param float $points
     * @param int $totalTardinessMinutes
     * @param \DateTime $periodStart
     * @param \DateTime $periodEnd
     * @param array $accumulatedOccurrences
     */
    public function __construct(User $subject, float $points, int $totalTardinessMinutes,
                                \DateTime $periodStart, \DateTime $periodEnd, array $accumulatedOccurrences)
    {
        parent::__construct($subject, $points, $totalTardinessMinutes);

        $this->periodStart = $periodStart;
        $this->periodEnd = $periodEnd;

        $this->accumulatedOccurrences = new ArrayCollection();

        foreach ($accumulatedOccurrences as $accumulatedOccurrence) {
            $this->includeOccurrence($accumulatedOccurrence);
        }
    }

    /**
     * The approve action must be cascaded for all children because these are not displayed in the admin interface.
     */
    public function approve()
    {
        parent::approve();

        foreach ($this->accumulatedOccurrences as $occurrence) {
            $occurrence->approve();
        }
    }

    /**
     * The reject action must be cascaded for all children because these are not displayed in the admin interface.
     */
    public function reject()
    {
        parent::reject();

        foreach ($this->accumulatedOccurrences as $occurrence) {
            $occurrence->reject();
        }
    }

    /**
     * @return \DateTime
     */
    public function getPeriodStart(): \DateTime
    {
        return $this->periodStart;
    }

    /**
     * @return \DateTime
     */
    public function getPeriodEnd(): \DateTime
    {
        return $this->periodEnd;
    }

    /**
     * @return TardinessOccurrence[]
     */
    public function getAccumulatedOccurrences()
    {
        return $this->accumulatedOccurrences;
    }

    /**
     * @param TardinessOccurrence $tardinessOccurrence
     */
    private function includeOccurrence($tardinessOccurrence)
    {
        $tardinessOccurrence->setCumulativeOccurrence($this);
        if (!$this->accumulatedOccurrences->contains($tardinessOccurrence)) {
            $this->accumulatedOccurrences->add($tardinessOccurrence);
        }
    }

}