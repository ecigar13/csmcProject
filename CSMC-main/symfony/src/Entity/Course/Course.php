<?php

namespace App\Entity\Course;

use App\Entity\Interfaces\ModifiableInterface;
use App\Entity\Misc\Subject;
use App\Entity\Traits\ModifiableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="course")
 *
 * @UniqueEntity(
 *     fields = {"name"},
 *     message = "Course {{ value }} already exists!"
 * )
 * @UniqueEntity(
 *     fields = {"number", "department"},
 *     message = "Course #{{ value }} and the department already exists!"
 * )
 */
class Course implements ModifiableInterface {
    use ModifiableTrait;
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Department", inversedBy="courses")
     * @ORM\JoinColumn(name="department_id", referencedColumnName="id")
     */
    private $department;

    /**
     * @ORM\Column(type="string", length=64, name="name")
     *
     * @Assert\Length(
     *      max = 64,
     *      maxMessage = "The Course name cannot be longer than {{ limit }} characters!"
     * )
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=4, name="number")
     *
     * @Assert\Length(
     *      min = 4,
     *      max = 4,
     *      exactMessage = "The length of Course number should be exactly {{ limit }}!",
     * )
     */
    private $number;

    /**
     * @ORM\OneToMany(targetEntity="Section", mappedBy="course")
     */
    private $sections;

    /**
     * @ORM\Column(type="boolean", name="supported")
     */
    private $supported;

    /**
     * @ORM\Column(type="string", name="description", length=8192, nullable=true)
     *
     * @Assert\Length(
     *      max = 8192,
     *      maxMessage = "The description cannot be longer than {{ limit }} characters!"
     * )
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Misc\Subject")
     * @ORM\JoinColumn(name="subject_id", referencedColumnName="id")
     */
    private $subject;

    /**
     * Constructor
     */
    public function __construct(Department $department, string $number, string $name, bool $supported, Subject $subject = null) {
        $this->department = $department;
        $this->number = $number;
        $this->name = $name;
        $this->supported = $supported;
        $this->subject = $subject;

        $this->sections = new ArrayCollection();
    }

    /**
     * Returns the course's id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the course's name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Sets the course's name
     *
     * @param string $name
     *
     * @return Course
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns the course's number
     *
     * @return string
     */
    public function getNumber() {
        return $this->number;
    }

    /**
     * Sets the course's number
     *
     * Number is typically 4 digits, e.g. 1336
     *
     * @param string $number
     *
     * @return Course
     */
    public function setNumber($number) {
        $this->number = $number;

        return $this;
    }

    /**
     * Returns the course's department
     *
     * @return \App\Entity\Course\Department
     */
    public function getDepartment() {
        return $this->department;
    }

    /**
     * Sets the course's department
     *
     * @param \App\Entity\Course\Department $department
     *
     * @return Course
     */
    public function setDepartment(\App\Entity\Course\Department $department = null) {
        $this->department = $department;

        return $this;
    }

    /**
     * Adds a section to the course
     *
     * @param \App\Entity\Course\Section $section
     *
     * @return Course
     */
    public function addSection(\App\Entity\Course\Section $section) {
        $this->sections[] = $section;

        return $this;
    }

    /**
     * Removes a section from the course
     *
     * @param \App\Entity\Course\Section $section
     */
    public function removeSection(\App\Entity\Course\Section $section) {
        $this->sections->removeElement($section);
    }

    /**
     * Returns the course's sections
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSections() {
        return $this->sections;
    }

    /**
     * Set supported
     *
     * @param boolean $supported
     *
     * @return Course
     */
    public function setSupported($supported) {
        $this->supported = $supported;

        return $this;
    }

    /**
     * Get supported
     *
     * @return boolean
     */
    public function getSupported() {
        return $this->supported;
    }

    public function getDescription() {
        return $this->description;
    }
}
