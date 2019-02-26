<?php

namespace App\Entity\Session;

use App\DataTransferObject\FileData;
use App\Entity\Course\Section;
use App\Entity\File\File;
use App\Entity\Traits\ModifiableTrait;
use App\Entity\Interfaces\ModifiableInterface;
use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="session")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
abstract class Session implements ModifiableInterface {
    use ModifiableTrait;

    const REVIEW_TYPE = 'review';
    const REWORK_TYPE = 'rework';
    const QUIZ_TYPE = 'quiz';

    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Request", inversedBy="session")
     * @ORM\JoinColumn(name="request_id", referencedColumnName="id", nullable=true)
     */
    private $request;

    /**
     * @ORM\Column(type="string", length=128, name="topic")
     */
    private $topic;

    /**
     * @ORM\Column(type="string", length=1024, name="description", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", name="student_instructions", length=1024, nullable=true)
     */
    private $studentInstructions;

    /**
     * @ORM\Column(type="string", name="mentor_instructions", length=1024, nullable=true)
     */
    private $mentorInstructions;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Entity\Course\Section")
     * @ORM\JoinTable(name="session_sections",
     *      joinColumns={@ORM\JoinColumn(name="session_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="section_id", referencedColumnName="id")}
     *      )
     */
    private $sections;

    /**
     * @ORM\ManyToOne(targetEntity="SessionType")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     */
    private $type;

    /**
     * @ORM\Column(type="boolean", name="graded")
     */
    private $graded;

    /**
     * @ORM\Column(type="boolean", name="numeric_grade")
     */
    private $numericGrade;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Entity\File\File", cascade={"persist", "remove"})
     * @ORM\JoinTable(name="session_files",
     *      joinColumns={@ORM\JoinColumn(name="session_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="file_id", referencedColumnName="id")}
     *      )
     */
    private $files;

    /**
     * @Assert\All(
     *      @Assert\File(
     *          maxSize = "5M",
     *          mimeTypes = {"application/pdf", "application/x-pdf", "application/msword", 
     *              "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
     *              "application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *              "application/vnd.ms-powerpoint",
     *              "application/vnd.openxmlformats-officedocument.presentationml.presentation",
     *              "application/rtf"},
     *          mimeTypesMessage = "Supported file types: .pdf, .doc, .docx, .xls, .xlsx, .ppt, .pptx, .rtf"
     *      )
     * )
     */
    private $uploadedFiles;

    /**
     * @ORM\Column(type="string", length=7, name="color", nullable=true)
     */
    private $color;

    public function __construct(SessionType $type, string $topic, string $description = null, string $studentInstructions = null, string $mentorInstructions = null, bool $graded, bool $numericGrade) {
        $this->topic = $topic;
        $this->description = $description;
        $this->studentInstructions = $studentInstructions;
        $this->mentorInstructions = $mentorInstructions;
        $this->type = $type;
        $this->graded = $graded;
        $this->numericGrade = $numericGrade;

        $this->sections = new ArrayCollection();
        $this->files = new ArrayCollection();
    }

    // could be useful
    abstract public function getStartDate();

    abstract public function getEndDate();

    abstract public function getAttendances();
    abstract public function getAttendance(User $user);

    public function update(SessionType $type, string $topic, string $description = null, string $studentInstructions = null, string $mentorInstructions = null, bool $graded, bool $numericGrade) {
        $this->topic = $topic;
        $this->description = $description;
        $this->studentInstructions = $studentInstructions;
        $this->mentorInstructions = $mentorInstructions;
        $this->type = $type;
        $this->graded = $graded;
        $this->numericGrade = $numericGrade;
    }

    public function attachFile(FileData $fileData, EntityManagerInterface $entityManager, array $metadata = null) {
        $this->files[] = File::fromUploadData($fileData, $entityManager, $metadata);

        return $this;
    }

    public function attachExistingFile(File $file) {
        $this->files[] = $file;
        return $this;
    }

    public function detachFile(File $file) {
        foreach($this->files as $f) {
            if($f->getId() == $file->getId()) {
                $this->files->removeElement($f);
                return;
            }
        }
    }

    public function setRequest(Request $request) {
        $this->request = $request;
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
     * Get request
     *
     * @return integer
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Get topic
     *
     * @return string
     */
    public function getTopic() {
        return $this->topic;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Get studentInstructions
     *
     * @return string
     */
    public function getStudentInstructions() {
        return $this->studentInstructions;
    }

    /**
     * Get mentorInstructions
     *
     * @return string
     */
    public function getMentorInstructions() {
        return $this->mentorInstructions;
    }

    /**
     * Get sections
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSections() {
        return $this->sections;
    }

    public function addSection(Section $section) {
        $this->sections->add($section);

        return $this;
    }

    public function getUploadedFiles() {
        return $this->uploadedFiles;
    }

    public function setUploadedFiles($files) {
        $this->uploadedFiles = $files;
    }

    /**
     * Get files
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFiles() {
        return $this->files;
    }

    /**
     * Get graded
     *
     * @return boolean
     */
    public function isGraded() {
        return $this->graded;
    }

    /**
     * Get numericGrade
     *
     * @return boolean
     */
    public function isNumericGrade() {
        return $this->numericGrade;
    }


    public function getInstructors() {
        $instructors = new ArrayCollection();
        foreach ($this->sections as $section) {
            $section_instructors = $section->getInstructors();
            foreach($section_instructors as $instructor) {
                if (!$instructors->contains($instructor)) {
                    $instructors->add($instructor);
                }
            }
        }

        return $instructors;
    }

    public function getStudents() {
        $students = array();

        foreach($this->sections as $section) {
            $students = array_merge($students, $section->getRoster()->toArray());
        }

        return $students;
    }

    /**
     * Get graded
     *
     * @return boolean
     */
    public function getGraded() {
        return $this->graded;
    }

    /**
     * Get numericGrade
     *
     * @return boolean
     */
    public function getNumericGrade() {
        return $this->numericGrade;
    }

    public function getColor() {
        return $this->color ?? $this->type->getColor();
    }

    public function getType() {
        return $this->type;
    }
}
