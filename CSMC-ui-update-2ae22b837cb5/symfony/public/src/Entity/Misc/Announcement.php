<?php

namespace App\Entity\Misc;

use App\Entity\Interfaces\ModifiableInterface;
use App\Entity\Traits\ModifiableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Misc\AnnouncementRepository")
 * @ORM\Table(name="announcement")
 *
 * @UniqueEntity(
 *     fields = {"subject", "startDate", "endDate"},
 *     message = "The subject, {{ value }}, start date, and end date already exists!"
 * )
 */
class Announcement implements ModifiableInterface {
    use ModifiableTrait;
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Entity\User\Role")
     * @ORM\JoinTable(name="announcement_roles",
     *      joinColumns={@ORM\JoinColumn(name="announcement_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")}
     *      )
     */
    private $roles;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Entity\User\UserGroup")
     * @ORM\JoinTable(name="announcement_groups",
     *      joinColumns={@ORM\JoinColumn(name="announcement_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     *      )
     */
    private $userGroups;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Entity\User\User")
     * @ORM\JoinTable(name="announcement_users",
     *      joinColumns={@ORM\JoinColumn(name="announcement_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     *      )
     */
    private $users;

    /**
     * @ORM\Column(type="string", name="subject", length=191, unique=true)
     *
     * @Assert\Length(
     *      max = 191,
     *      maxMessage = "The subject cannot be longer than {{ limit }} characters!"
     * )
     */
    private $subject;

    /**
     * @ORM\Column(type="string", name="message", length=8192)
     *
     * @Assert\Length(
     *      max = 8192,
     *      maxMessage = "The message cannot be longer than {{ limit }} characters"
     * )
     */
    private $message;

    /**
     * @ORM\Column(type="date", name="post_date")
     */
    private $postDate;

    /**
     * @ORM\Column(type="date", name="start_date")
     */
    private $startDate;

    /**
     * @ORM\Column(type="date", name="end_date")
     */
    private $endDate;

    /**
     * @ORM\Column(type="boolean", name="active")
     */
    private $active;

    /**
     * Constructor
     */
    public function __construct(string $subject, string $message, \DateTime $start, \DateTime $end) {
        $this->subject = $subject;
        $this->message = $message;

        $this->startDate = $start;
        $this->endDate = $end;

        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();

        $this->postDate = new \DateTime();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    public function getSubject() {
        return $this->subject;
    }

    public function getStartDate() {
        return $this->startDate;
    }

    public function getEndDate() {
        return $this->endDate;
    }

    /**
     * Set message
     *
     * @param string $message
     *
     * @return Announcement
     */
    public function setMessage($message) {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Set postDate
     *
     * @param \DateTime $postDate
     *
     * @return Announcement
     */
    public function setPostDate($postDate) {
        $this->postDate = $postDate;

        return $this;
    }

    /**
     * Get postDate
     *
     * @return \DateTime
     */
    public function getPostDate() {
        return $this->postDate;
    }

    /**
     * Add role
     *
     * @param \App\Entity\User\Role $role
     *
     * @return Announcement
     */
    public function addRole(\App\Entity\User\Role $role) {
        $this->roles[] = $role;

        return $this;
    }

    /**
     * Remove role
     *
     * @param \App\Entity\User\Role $role
     */
    public function removeRole(\App\Entity\User\Role $role) {
        $this->roles->removeElement($role);
    }

    /**
     * Get roles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRoles() {
        return $this->roles;
    }

    /**
     * Add user
     *
     * @param \App\Entity\User\User $user
     *
     * @return Announcement
     */
    public function addUser(\App\Entity\User\User $user) {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param \App\Entity\User\User $user
     */
    public function removeUser(\App\Entity\User\User $user) {
        $this->users->removeElement($user);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers() {
        return $this->users;
    }

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return Announcement
     */
    public function setActive($active) {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive() {
        return $this->active;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     *
     * @return Announcement
     */
    public function setDuration($duration) {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer
     */
    public function getDuration() {
        return $this->duration;
    }
}
