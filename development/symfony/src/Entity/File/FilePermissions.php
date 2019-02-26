<?php

namespace App\Entity\File;

use Doctrine\ORM\Mapping as ORM;

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
}