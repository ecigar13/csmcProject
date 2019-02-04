<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class FileController extends Controller {
    /**
     * @Route("/file", name="file")
     */
    public function fileAction() {
        return $this->render('role/admin/file_manager.html.twig');
    }

    /**
     * @Route("/download/{id}", name="download")
     */
    public function downloadAction(\App\Entity\File\File $file) {
        $this->denyAccessUnlessGranted([
            'admin',
            'developer',
            'instructor',
            'mentor'
        ]);

        return $this->file(new File($this->getParameter('dir.uploads') . '/' . $file->getPhysicalPath()), $file->getName(), ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }
}