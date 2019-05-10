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
     * @ORM\ManyToMany(targetEntity="App\Entity\User\User", inversedBy="virtualFiles")
     * @ORM\JoinTable(name="file_permissions_users")
     */
    private $users;

    /**
     * Many role have Many files.
     * @ORM\ManyToMany(targetEntity="App\Entity\User\Role", inversedBy="virtualFiles")
     * @ORM\JoinTable(name="file_permissions_roles")
     *
	 */
    private $roles;
    /**
     * .
     * @ORM\OneToMany(targetEntity="VirtualFile", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="VirtualFile", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
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
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @param VirtualFile $child
     *
     * @return VirtualFile
     */
    public function addChild(VirtualFile $child) {
        $this->children->add($child);
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

    public function getOwner()
    {
        return $this->owner;
    }

    public function getUsers()
    {
        return $this->users->toArray();;
    }

    public function getRoles()
    {
        return $this->roles->toArray();;
    }

    public function clearUsers()
    {

        foreach($this->users as $user)   
            $this->users->removeElement($user);
        // $category->removeProject($this);
    }

    public function clearRoles()
    {
        // if (!$this->roles->contains($role)) {
        //     return;
        // }   
        foreach($this->roles as $role)  
            $this->roles->removeElement($role);
        // $category->removeProject($this);
    }

    
    public function getChildren()
    {
        return $this->children->toArray();;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @return self
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
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

    public function __toString() {
        return $this->name;
    }
}