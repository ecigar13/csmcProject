<?php

namespace App\Entity\File;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * @ORM\Entity()
 */
class FilePermissions {
    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="VirtualFile", inversedBy="permissions")
     * @ORM\JoinColumn(name="virtual_file_id", referencedColumnName="id")
     */
    private $virtualFile;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\Column(type="boolean", name="view")
     */
    private $view;

    /**
     * @ORM\Column(type="boolean", name="edit")
     */
    private $edit;

    public function __construct(User $user, bool $view , bool $edit) {
            $this->user = $user;
            $this->view = $view;
            $this->edit = $edit;
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

    /**
     * @return mixed
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param mixed $view
     *
     * @return self
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEdit()
    {
        return $this->edit;
    }

    /**
     * @param mixed $edit
     *
     * @return self
     */
    public function setEdit($edit)
    {
        $this->edit = $edit;

        return $this;
    }
}