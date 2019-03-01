<?php
/**
 * Created by IntelliJ IDEA.
 * User: snehachandra
 * Date: 14/10/18
 * Time: 03:26
 */

namespace App\Entity\Penalty;

use Doctrine\ORM\Mapping as ORM;

/**
 * @package App\Entity\Penalty
 * @ORM\Entity
 */
class CourseOfAction
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
    private $threshold;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $description;

    /**
     * CourseOfAction constructor.
     * @param float $threshold
     * @param string $description
     */
    public function __construct($threshold, $description)
    {
        $this->threshold = $threshold;
        $this->description = $description;
    }

    public function getId() {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getThreshold() {
        return $this->threshold;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

}