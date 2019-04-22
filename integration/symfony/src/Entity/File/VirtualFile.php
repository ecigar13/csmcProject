<?php

namespace App\Entity\File;

use App\Entity\User\User;
use App\Entity\User\Role;
use App\Entity\File\Permission;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"virtual" = "VirtualFile", "file" = "File","dir" = "Directory","link" = "Link"})
 */
class VirtualFile {
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", name="name", length=64)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $owner;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User\User",  mappedBy="vitualFiles")
     */
    private $users;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User\Role",  mappedBy="vitualFiles")
     */
    private $roles;

    /**
     * @ORM\ManyToOne(targetEntity="VirtualFile")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;

    public function __construct(string $name, User $owner) {
        $this->name = $name;
        $this->owner = $owner;
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function giveIndividualPermission(User $user) {

    }
    public function giveRolePermission(Role $role) {

    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}