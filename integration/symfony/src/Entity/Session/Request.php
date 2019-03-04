<?php

namespace App\Entity\Session;

use App\Annotation\Timestampable;
use App\DataTransferObject\FileData;
use App\DBAL\Types\RequestStatusType;
use App\Entity\File\File;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Session\RequestRepository")
 * @ORM\Table(name="request")
 * @Timestampable
 */
class Request {
    use TimestampableTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\OneToOne(targetEntity="Session", mappedBy="request")
     */
    private $session;

    /**
     * @ORM\Column(type="date", name="start_date")
     */
    private $startDate;

    /**
     * @ORM\Column(type="date", name="end_date")
     */
    private $endDate;

    /**
     * @ORM\Column(type="string", name="topic", length=128)
     */
    private $topic;

    /**
     * @ORM\Column(type="string", name="student_instructions", length=256, nullable=true)
     */
    private $studentInstructions;

    /**
     * @ORM\ManyToOne(targetEntity="SessionType")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     */
    private $type;

    /**
     * @ORM\Column(type="request_status", name="status")
     */
    private $status;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Entity\Course\Section")
     * @ORM\JoinTable(name="request_sections",
     *     joinColumns={@ORM\JoinColumn(name="request_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="section_id", referencedColumnName="id")}
     *     )
     */
    private $sections;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Entity\File\File", cascade={"persist"})
     * @ORM\JoinTable(name="request_files",
     *      joinColumns={@ORM\JoinColumn(name="request_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="file_id", referencedColumnName="id")}
     *      )
     */
    private $files;

    /**
     * Constructor
     */
    public function __construct(SessionType $type, User $requester = null, string $topic, \DateTime $start = null, \DateTime $end = null, string $studentInstructions = null, array $sections) {
        $this->type = $type;
        $this->user = $requester;
        $this->topic = $topic;
        $this->startDate = $start;
        $this->endDate = $end;
        $this->studentInstructions = $studentInstructions;

        $this->sections = new ArrayCollection($sections);

        $this->status = RequestStatusType::NEW;

        $this->files = new ArrayCollection();
    }

    public function attachFile(FileData $fileData, EntityManagerInterface $entityManager, array $metadata = null) {
        $this->files[] = File::fromUploadData($fileData, $entityManager, $metadata);

        return $this;
    }

    public function update(SessionType $type, string $topic, \DateTime $start = null, \DateTime $end = null, string $studentInstructions = null, array $sections) {
        $this->type = $type;
        $this->topic = $topic;
        $this->startDate = $start;
        $this->endDate = $end;
        $this->studentInstructions = $studentInstructions;

        $this->sections = new ArrayCollection($sections);
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
     * Set timeRequested
     *
     * @param \DateTime $timeRequested
     *
     * @return Request
     */
    public function setTimeRequested($timeRequested) {
        $this->timeRequested = $timeRequested;

        return $this;
    }

    /**
     * Get timeRequested
     *
     * @return \DateTime
     */
    public function getTimeRequested() {
        return $this->timeRequested;
    }

    /**
     * Set timeLastUpdated
     *
     * @param \DateTime $timeLastUpdated
     *
     * @return Request
     */
    public function setTimeLastUpdated($timeLastUpdated) {
        $this->timeLastUpdated = $timeLastUpdated;

        return $this;
    }

    /**
     * Get timeLastUpdated
     *
     * @return \DateTime
     */
    public function getTimeLastUpdated() {
        return $this->timeLastUpdated;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return Request
     */
    public function setStartDate($startDate) {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate() {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return Request
     */
    public function setEndDate($endDate) {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate() {
        return $this->endDate;
    }

    /**
     * Set topic
     *
     * @param string $topic
     *
     * @return Request
     */
    public function setTopic($topic) {
        $this->topic = $topic;

        return $this;
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
     * Set studentInstructions
     *
     * @param string $studentInstructions
     *
     * @return Request
     */
    public function setStudentInstructions($studentInstructions) {
        $this->studentInstructions = $studentInstructions;

        return $this;
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
     * Set mentorInstructions
     *
     * @param string $mentorInstructions
     *
     * @return Request
     */
    public function setMentorInstructions($mentorInstructions) {
        $this->mentorInstructions = $mentorInstructions;

        return $this;
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
     * Set user
     *
     * @param \App\Entity\User\User $user
     *
     * @return Request
     */
    public function setUser(\App\Entity\User\User $user = null) {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \App\Entity\User\User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Set type
     *
     * @param \App\Entity\Session\RequestType $type
     *
     * @return Request
     */
    public function setType(\App\Entity\Session\RequestType $type = null) {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \App\Entity\Session\RequestType
     */
    public function getType() {
        return $this->type;
    }

    /**
     *
     */
    public function setStatus(string $status = null) {
        $this->status = $status;

        return $this;
    }

    /**
     *
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Add file
     *
     * @param \App\Entity\File\File $file
     *
     * @return Request
     */
    public function addFile(\App\Entity\File\File $file) {
        $this->files[] = $file;

        return $this;
    }

    /**
     * Remove file
     *
     * @param \App\Entity\File\File $file
     */
    public function removeFile(\App\Entity\File\File $file) {
        $this->files->removeElement($file);
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
     * Add section
     *
     * @param \App\Entity\Course\Section $section
     *
     * @return Request
     */
    public function addSection(\App\Entity\Course\Section $section) {
        $this->sections[] = $section;

        return $this;
    }

    /**
     * Remove section
     *
     * @param \App\Entity\Course\Section $section
     */
    public function removeSection(\App\Entity\Course\Section $section) {
        $this->sections->removeElement($section);
    }

    /**
     * Get sections
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSections() {
        return $this->sections;
    }

    public function getUploadedFiles() {
        return $this->uploadedFiles;
    }

    public function setUploadedFiles($files) {
        $this->uploadedFiles = $files;
    }

    /**
     * Set session
     *
     * @param \App\Entity\Session\Session $session
     *
     * @return Request
     */
    public function setSession(\App\Entity\Session\Session $session = null) {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session
     *
     * @return \App\Entity\Session\Session
     */
    public function getSession() {
        return $this->session;
    }
}
