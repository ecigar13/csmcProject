<?php

namespace App\Entity\File;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * @ORM\Entity()
 */
class FilePermissionsIndividual {
    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="VirtualFile", inversedBy="permissions")
     * @ORM\JoinColumn(name="virtual_file_id", referencedColumnName="id")
     */
    private $virtualFile;


    /**
	 * @ORM\ManyToOne(targetEntity="App\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    public function __construct(User $user) {
            $this->user = $user;
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
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     *
     * @return self
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
}