<?php

namespace App\Controller\Entity\User;

use App\Entity\User\User;
use App\Entity\User\Specialty;
use App\Form\User\SpecialtyType;
use App\Form\User\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class UserController extends Controller {
    /**
     * @Route("/user/profile", name="user_profile")
     */
    public function userProfileAction() {
        return $this->forward('App:Entity\User\Info:infoView', array('id' => $this->getUser()->getId()));
    }

    /**
     * @Route("/ajax/user/reset_card", name="user_reset_card")
     */
    public function userResetCardAction(Request $request) {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedHttpException();
        }

        if(!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $this->getUser()->setScancode(null);
        $this->getUser()->setCardId(null);
        $this->getUser()->setFac(null);

        $this->getDoctrine()->getManager()->flush();

        return new Response(200);
    }
}

