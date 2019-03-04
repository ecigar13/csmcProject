<?php

namespace App\Utils;

use Doctrine\ORM\EntityManagerInterface;

class ReportManager {
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function generateReport() {

    }
}