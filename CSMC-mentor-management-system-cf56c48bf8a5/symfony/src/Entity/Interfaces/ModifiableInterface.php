<?php

namespace App\Entity\Interfaces;

use App\Entity\User\User;

interface ModifiableInterface {
    public function getLastModifiedOn();
    public function setLastModifiedOn();

    public function setLastModifiedBy(User $user);
    public function getLastModifiedBy();

    public function setCreatedOn();
    public function getCreatedOn();

    public function setCreatedBy(User $user);
    public function getCreatedBy();
}