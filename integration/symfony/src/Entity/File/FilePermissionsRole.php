<?php

namespace App\Entity\File;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User\Role;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * @ORM\Entity()
 */
class FilePermissionsRole {
    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="VirtualFile", inversedBy="permissions")
     * @ORM\JoinColumn(name="virtual_file_id", referencedColumnName="id")
     */
    private $virtualFile;


    /**
	 * @ORM\ManyToOne(targetEntity="App\Entity\User\Role")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     */
    private $role;

    public function __construct(Role $role) {
            $this->role = $role;
        }
    /**
     * @return mixed
     */
    public function getVirtualFile()
    {
        return $this->virtualFile;
    }

    /**
     * @param mixed $virtualFile
     *
     * @return self
     */
    public function setVirtualFile($virtualFile)
    {
        $this->virtualFile = $virtualFile;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param mixed $role
     *
     * @return self
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }
}