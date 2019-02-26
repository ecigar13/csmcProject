<?php

namespace App\Repository\Misc;

use Doctrine\ORM\EntityRepository;

class HolidayRepository extends EntityRepository {
    public function findUpcoming() {
        // $qb = $this->createQueryBuilder('h');
    }
}