<?php

namespace App\Entity\Course;

use App\Entity\Interfaces\ModifiableInterface;
use App\Entity\Traits\ModifiableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Represents a department at the university
 *
 * A department is what courses are classified under. For example, the primary
 * department for the CSMC is Computer Science.
 *
 * @ORM\Entity
 * @ORM\Table(name="department")
 *
 * @UniqueEntity("name", message="Department {{ value }} already exists!")
 * @UniqueEntity("abbreviation", message="Department with the same abbreviation already exists!")
 */
class Department implements ModifiableInterface {
    use ModifiableTrait;
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="name", length=32)
     *
     * @Assert\Length(
     *      max = 32,
     *      maxMessage = "Department name cannot be longer than {{ limit }} characters"
     * )
     */
    private $name;

    /**
     * @ORM\Column(type="string", name="abbreviation", length=3)
     *
     * @Assert\Length(
     *      max = 4,
     *      maxMessage = "Abbreviation cannot be longer than {{ limit }} characters"
     * )
     */
    private $abbreviation;

    /**
     * @ORM\Column(type="string", name="admin_notes",length=8192,nullable=true)
     *
     * @Assert\Length(
     *      max = 8192,
     *      maxMessage = "Admin Notes cannot be longer than {{ limit }} characters"
     * )
     */
    private $admin_notes;

    /**
     * @ORM\OneToMany(targetEntity="Course", mappedBy="department")
     */
    private $courses;

    /**
     * Constructor
     */
    public function __construct(string $name, string $abbreviation) {
        $this->name = $name;
        $this->abbreviation = $abbreviation;

        $this->courses = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Returns the department's id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Sets the department's name
     *
     * @param string $name
     *
     * @return Department
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns the department's name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Sets the department's abbreviation
     *
     * @param string $abbreviation
     *
     * @return Department
     */
    public function setAbbreviation($abbreviation) {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    /**
     * Returns the department's abbreviation
     *
     * @return string
     */
    public function getAbbreviation() {
        return $this->abbreviation;
    }

    /**
     * Add a course to the department
     *
     * @param \App\Entity\Course\Course $course
     *
     * @return Department
     */
    public function addCourse(\App\Entity\Course\Course $course) {
        $this->courses[] = $course;

        return $this;
    }

    /**
     * Remove a course from the department
     *
     * @param \App\Entity\Course\Course $course
     */
    public function removeCourse(\App\Entity\Course\Course $course) {
        $this->courses->removeElement($course);
    }

    /**
     * Returns the department's courses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCourses() {
        return $this->courses;
    }

    public function getAdminNotes() {
        return $this->admin_notes;
    }
}
