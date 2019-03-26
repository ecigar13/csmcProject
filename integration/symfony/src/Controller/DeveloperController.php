<?php

namespace App\Controller;

use App\Entity\File\File;
use App\Entity\File\VirtualFile;
use App\Entity\Misc\Swipe;
use App\Utils\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dev", name="dev_")
 */
class DeveloperController extends Controller {
    /**
     * @Route("/", name="home")
     */
    public function homeAction() {
        return $this->render('role/developer/home.html.twig');
    }


    /**
     * @Route("/files", name="files")
     */
    public function fileViewAction() {
        return $this->render('role/developer/files.html.twig');
    }

    /**
     * @Route("/files/feed", name="files_feed")
     */
    public function filesFeedAction(Serializer $serializer) {
        $files = $this->getDoctrine()
            ->getRepository(VirtualFile::class)
            ->findAll();

        $json = $serializer->serialize($files, array(
            'attributes' => array(
                'id',
                'name',
                'owner' => [
                    'firstName',
                    'lastName',
                    'username'
                ],
                'metadata' => [
                    'key',
                    'value'
                ],
                'hash' => [
                    'size',
                    'path'
                ],
            )
        ));

        return JsonResponse::fromJsonString($json);
    }

    /**
     * @Route("/swipes", name="swipes")
     */
    public function swipeViewAction() {
        return $this->render('role/developer/swipes.html.twig');
    }

    /**
     * @Route("/swipes/feed", name="swipes_feed")
     */
    public function swipesFeedAction(Serializer $serializer) {
        $swipes = $this->getDoctrine()
            ->getRepository(Swipe::class)
            ->findAll();

        $json = $serializer->serialize($swipes, array(
            'attributes' => array(
                'id',
                'ip' => [
                    'address',
                    'blocked'
                ],
                'time',
                'user' => [
                    'firstName',
                    'lastName',
                    'username'
                ],
                'legacy',
                'valid'
            )
        ));

        return JsonResponse::fromJsonString($json);
    }
}