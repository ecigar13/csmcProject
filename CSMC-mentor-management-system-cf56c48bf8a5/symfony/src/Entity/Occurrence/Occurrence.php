<?php


namespace App\Entity\Occurrence;


use App\Entity\User\User;
use App\Utils\DateTimeService;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * Base class for all occurrence types.
 *
 * @ORM\Entity(repositoryClass="App\Repository\Occurrence\OccurrenceRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="disc", type="string")
 * @ORM\Table(indexes={@Index(name="IDX_OCCURRENCE_STATUS", columns={"status"})})
 *
 * @package App\Entity\Occurrence
 */
abstract class Occurrence
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @var string
     */
    private $id;

    /**
     * @ORM\Column(type="occurrence_status")
     *
     * @var string
     */
    private $status;

    /**
     * @ORM\Column(type="datetime")
     *
     * @var \DateTime
     */
    private $creationDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User", inversedBy="occurrences")
     *
     * @var User
     */
    private $subject;

    /**
     * @ORM\Column(type="float")
     *
     * @var float
     */
    private $points;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string|null
     */
    private $adminNotes;

    /**
     * @param User $subject
     * @param float $points
     * @param string $adminNotes
     * @param DateTimeService|null $time
     */
    public function __construct(User $subject, float $points = null, string $adminNotes = null, DateTimeService $time = null)
    {
        $this->subject = $subject;
        $subject->addOccurrence($this);

        $this->points = $points;
        $this->adminNotes = $adminNotes;

        if ($time != null) {
            $this->creationDate = $time->now();
        } else {
            $this->creationDate = new \DateTime();
        }

        $this->status = Occurrence::STATUS_PENDING;
    }

    /**
     * Marks this occurrence as approved.
     */
    public function approve()
    {
        $this->status = self::STATUS_APPROVED;
    }

    /**
     * Marks this occurrence as rejected.
     */
    public function reject()
    {
        $this->status = self::STATUS_REJECTED;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @param float $points
     */
    public function setPoints(float $points)
    {
        $this->points = $points;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate(): \DateTime
    {
        return $this->creationDate;
    }

    /**
     * @return User
     */
    public function getSubject(): User
    {
        return $this->subject;
    }

    /**
     * @return float
     */
    public function getPoints(): float
    {
        return $this->points;
    }

    /**
     * @return null|string
     */
    public function getAdminNotes()
    {
        return $this->adminNotes;
    }

    /**
     * @param null|string $adminNotes
     */
    public function setAdminNotes($adminNotes)
    {
        $this->adminNotes = $adminNotes;
    }

}