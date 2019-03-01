<?php

namespace App\Entity\User\Info;

use Doctrine\ORM\Mapping as Orm;

/**
 * @ORM\Entity
 * @ORM\Table(name="dietary_restriction")
 */
class DietaryRestriction {
    // TODO use http://www.webster.edu/specialevents/planning/food-information.html for form stuff
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="text", name="name")
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="DietaryRestrictionCategory")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    private $category;

    public function __toString() {
        return $this->name;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return DietaryRestriction
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set category
     *
     * @param \App\Entity\User\Info\DietaryRestrictionCategory $category
     *
     * @return DietaryRestriction
     */
    public function setcategory(\App\Entity\User\Info\DietaryRestrictionCategory $category = null) {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \App\Entity\User\Info\DietaryRestrictionCategory
     */
    public function getcategory() {
        return $this->category;
    }
}
