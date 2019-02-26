<?php

namespace App\Entity\Misc;


use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="policy")
 */
class Policy {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="name", length=64)
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Entity\File\File")
     * @ORM\JoinTable(name="policy_files",
     *      joinColumns={@ORM\JoinColumn(name="policy_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="file_id", referencedColumnName="id")}
     *      )
     */
    private $file;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Entity\User\Role")
     * @ORM\JoinTable(name="policy_roles",
     *     joinColumns={@ORM\JoinColumn(name="policy_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")}
     *     )
     */
    private $roles;

    /**
     * Constructor
     */
    public function __construct() {
        $this->file = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Policy
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
     * Add file
     *
     * @param \App\Entity\File\File $file
     *
     * @return Policy
     */
    public function addFile(\App\Entity\File\File $file) {
        $this->file[] = $file;

        return $this;
    }

    /**
     * Remove file
     *
     * @param \App\Entity\File\File $file
     */
    public function removeFile(\App\Entity\File\File $file) {
        $this->file->removeElement($file);
    }

    /**
     * Get file
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * Add role
     *
     * @param \App\Entity\User\Role $role
     *
     * @return Policy
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
}
