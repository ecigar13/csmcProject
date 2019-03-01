<?php

namespace App\Entity\Course;

use App\Entity\Interfaces\ModifiableInterface;
use App\Entity\Misc\Semester;
use App\Entity\Traits\ModifiableTrait;
use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Course\SectionRepository")
 * @ORM\Table(name="section")
 */
class Section implements ModifiableInterface {
    use ModifiableTrait;
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Course", inversedBy="sections")
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id")
     */
    private $course;

    /**
     * @ORM\Column(type="string", length=4, name="number")
     *
     * @Assert\Length(
     *      min = 3,
     *      max = 3,
     *      exactMessage = "The length of Section number should be {{ limit }}!",
     * )
     */
    private $number;

    /**
     * @ORM\Column(type="string", name="description", length=8192, nullable=true)
     *
     * @Assert\Length(
     *      max = 8192,
     *      maxMessage = "The description cannot be longer than {{ limit }} characters"
     * )
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Misc\Semester")
     * @ORM\JoinColumn(name="semester_id", referencedColumnName="id")
     */
    private $semester;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Entity\User\User")
     * @ORM\JoinTable(name="section_instructors",
     *     joinColumns={@ORM\JoinColumn(name="section_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private $instructors;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Entity\User\User")
     * @ORM\JoinTable(name="section_tas",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="section_id", referencedColumnName="id")}
     * )
     */
    private $teaching_assistants;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Entity\User\User")
     * @ORM\JoinTable(name="section_students",
     *     joinColumns={@ORM\JoinColumn(name="section_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private $students;

    /**
     * Constructor
     */
    public function __construct(Course $course, string $number, Semester $semester, User $instructor = null) {
        $this->course = $course;
        $this->number = $number;
        $this->semester = $semester;
        $this->instructors = new ArrayCollection();
        if($instructor) {
            $this->instructors[] = $instructor;
        }

        $this->teaching_assistants = new ArrayCollection();
        $this->students = new ArrayCollection();
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
     * Set number
     *
     * @param string $number
     *
     * @return Section
     */
    public function setNumber($number) {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return string
     */
    public function getNumber() {
        return $this->number;
    }

    /**
     * Set course
     *
     * @param \App\Entity\Course\Course $course
     *
     * @return Section
     */
    public function setCourse(Course $course = null) {
        $this->course = $course;

        return $this;
    }

    /**
     * Get course
     *
     * @return \App\Entity\Course\Course
     */
    public function getCourse() {
        return $this->course;
    }

    /**
     * Set semester
     *
     * @param \App\Entity\Misc\Semester $semester
     *
     * @return Section
     */
    public function setSemester(Semester $semester = null) {
        $this->semester = $semester;

        return $this;
    }

    /**
     * Get semester
     *
     * @return \App\Entity\Misc\Semester
     */
    public function getSemester() {
        return $this->semester;
    }

    /**
     * Set instructor
     *
     * @param \App\Entity\User\User $instructor
     *
     * @return Section
     */
    public function addInstructor(User $instructor) {
        $this->instructors[] = $instructor;

        return $this;
    }

    /**
     * Get instructors
     */
    public function getInstructors() {
        return $this->instructors;
    }

    /**
     * Add teachingAssistant
     *
     * @param \App\Entity\User\User $teachingAssistant
     *
     * @return Section
     */
    public function addTeachingAssistant(User $teachingAssistant) {
        $this->teaching_assistants[] = $teachingAssistant;

        return $this;
    }

    /**
     * Remove teachingAssistant
     *
     * @param \App\Entity\User\User $teachingAssistant
     */
    public function removeTeachingAssistant(User $teachingAssistant) {
        $this->teaching_assistants->removeElement($teachingAssistant);
    }

    /**
     * Get teachingAssistants
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTeachingAssistants() {
        return $this->teaching_assistants;
    }

    /**
     * Add student
     *
     * @param \App\Entity\User\User $student
     *
     * @return Section
     */
    public function enroll(User $student) {
        $this->students[] = $student;

        return $this;
    }

    public function hasStudent(User $student) {
        return $this->students->contains($student);
    }

    public function setDescription(string $descr) {
        $this->description = $descr;
    }

    /**
     * Remove student
     *
     * @param \App\Entity\User\User $student
     */
    public function removeStudent(User $student) {
        $this->students->removeElement($student);
    }

    /**
     * Get students
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRoster() {
        return $this->students;
    }

    public function __toString() {
        return $this->course->getDepartment()
                   ->getAbbreviation() . ' ' . $this->course->getNumber() . '.' . $this->number;
    }

    public function getDescription() {
        return $this->description;
    }
}
