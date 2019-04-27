<?php

namespace App\DataTransferObject;

use App\Entity\User\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This is used to create an object from an uploaded file. User should call File::fromUploadFile to create a file object with its hash signature.
 */
class FileData {
    public $file;
    public $user;
    public $path;

    public function  __construct(UploadedFile $file=null, User $user=null, string $path=''){
      $this->file = $file;
      $this->user = $user;
      $this->path = $path;
    }
}