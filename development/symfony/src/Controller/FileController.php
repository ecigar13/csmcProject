<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class FileController extends Controller {

    /**
     * @Route("/file", name="file")
     */
    public function fileAction() {
        return $this->render('role/admin/file_manager.html.twig');
    }
}