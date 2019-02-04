<?php

namespace App\Controller;

use App\Entity\Misc\Room;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {
    /**
     * @Route("/", name="home")
     */
    public function index() {
        $session = $this->get('session');

        $successMessage = null;
        // get message if there is one
        foreach ($session->getFlashBag()->get('success', array()) as $message) {
            $successMessage = $message;
        }

        if($this->isGranted('admin')) {
            $this->redirectToRoute('admin_home');
        }

        // get announcements

        $rooms = $this->getDoctrine()
            ->getRepository(Room::class)
            ->findActive();
        return $this->render('shared/home/home.html.twig', array(
            'successMessage' => $successMessage,
            'announcements' => array(),
            'rooms' => $rooms,
        ));
    }
}
