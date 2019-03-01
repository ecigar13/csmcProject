<?php
/**
 * Created by IntelliJ IDEA.
 * User: snehachandra
 * Date: 14/10/18
 * Time: 03:26
 */

namespace App\Entity\Occurrence;

use Doctrine\ORM\Mapping as ORM;

/**
 * @package App\Entity\Occurrence
 * @ORM\Entity
 */
class OccurrenceType
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=false)
     * @var float
     */
    private $defaultPoints;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $description;

    /**
     * OccurrenceType constructor.
     * @param float $defaultPoints
     * @param string $description
     */
    public function __construct($defaultPoints, $description)
    {
        $this->defaultPoints = $defaultPoints;
        $this->description = $description;
    }
    
    public function getId() {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getDefaultPoints() {
        return $this->defaultPoints;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

}