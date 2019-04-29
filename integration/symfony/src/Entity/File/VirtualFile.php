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
     * Many role have Many files.
     * @ORM\ManyToMany(targetEntity="App\Entity\User\User", inversedBy="vitualFiles")
     * @ORM\JoinTable(name="user_permissions")
     */
    private $users;

    /**
     * Many role have Many files.
     * @ORM\ManyToMany(targetEntity="App\Entity\User\Role", inversedBy="vitualFiles")
     * @ORM\JoinTable(name="role_permissions")
     */
    
    private $roles;

    /**
     * @ORM\ManyToOne(targetEntity="VirtualFile")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;

    /**
     * @ORM\Column(type="string", name="path", length=4000)
     */
    private $path;

    /**
    * @ORM\Column(type="datetime",name="created", nullable=false, options={"default":"CURRENT_TIMESTAMP"})
    * @ORM\Version
    * @var string
    */
    protected $created;


    public function __construct(string $name, User $owner=null, string $path = '') {
        $this->name = $name;
        $this->owner = $owner;
        $this->path = $path;
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set instructor
     *
     * @param \App\Entity\User\User $user
     *
     * @return VirtualFile
     */
    public function addUser(User $user) {
        $this->users->add($user);
        return $this;
    }
    /**
     * Set instructor
     *
     * @param \App\Entity\User\Role $role
     *
     * @return VirtualFile
     */
    public function addRole(Role $role) {
        $this->roles->add($role);
        return $this;
    }

    public function giveDate() {

        $to=$this->created;
        // list($part1,$part2) = explode(' ', $to);
        // list($day, $month, $year) = explode('-', $part1);
        // list($hours, $minutes,$seconds) = explode(':', $part2);
        // if($hours>5)
        //     $day=$day-1;
        // $date =  mktime($month, $day, $year);
        // echo $timeto;
        return $to;
    }
    
    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     */
    public function setName(string $name)
    {
        return $this->name = $name;
    }
    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }
    /**
     * @param mixed $parent
     * 
     * @return self
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get logical path in the database, not physical path.
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     *
     * @return self
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }
}