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

    public function giveIndividualPermission(User $user) {

    }
    public function giveRolePermission(Role $role) {

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
}