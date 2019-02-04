<?php

namespace App\Controller;

use App\Entity\Misc\Announcement;
use App\Entity\Misc\Holiday;
use App\Entity\Misc\OperationHours;
use App\Entity\Misc\Room;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {
    /**
     * @Route("/", name="home")
     */
    public function index() {
        // get announcements

        $hours = $this->getDoctrine()
            ->getRepository(OperationHours::class)
            ->findAll();

        // $holidays = $this->getDoctrine()
        //     ->getRepository(Holiday::class)
        //     ->findUpcoming();

        $rooms = $this->getDoctrine()
            ->getRepository(Room::class)
            ->findActive();

        $announcements = $this->getDoctrine()
            ->getRepository(Announcement::class)
            ->findActiveFor($this->getUser());

        return $this->render('shared/home/home.html.twig', array(
            'announcements' => $announcements,
            'hours' => $hours,
            'holidays' => null,
            'rooms' => $rooms,
        ));
    }
}
