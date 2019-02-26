<?php

namespace App\DataTransferObject;

use App\Entity\User\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileData {
    public $file;
    public $user;

    public function __construct(UploadedFile $file, User $user) {
        $this->file = $file;
        $this->user = $user;
    }
}