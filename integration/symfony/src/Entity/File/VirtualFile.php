<?php

namespace App\Entity\File;

use App\Entity\User\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"virtual" = "VirtualFile", "file" = "File"})
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
     * @ORM\OneToMany(targetEntity="FilePermissions", mappedBy="virtualFile")
     */
    private $permissions;

    /**
     * @ORM\ManyToOne(targetEntity="VirtualFile")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;

    public function __construct(string $name, User $owner = null) {
        $this->name = $name;
        $this->owner = $owner;
    }

    public function givePermission(User $user) {

    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
    
    public function setName(string $name){
        $this->name = $name;
    }

    public function getName(){
        return $this->name;
    }
}