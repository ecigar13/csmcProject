<?php

namespace App\Entity\Course;

use App\Entity\Interfaces\ModifiableInterface;
use App\Entity\Traits\ModifiableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="aggregate_section")
 */
class AggregateSection {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;
    //
    // /**
    //  * @ORM\ManyToMany(targetEntity="Section")
    //  * @ORM\JoinTable(name="aggregated_sections",
    //  *     joinColumns={@ORM\JoinColumn(name="aggregate_id", referencedColumnName="id")},
    //  *     inverseJoinColumns={@ORM\JoinColumn(name="section_id", referencedColumnName="id")}
    //  * )
    //  */
    // private $sections;
}