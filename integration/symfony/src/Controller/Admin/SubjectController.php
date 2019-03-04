<?php

namespace App\Controller\Admin;

use App\Entity\Misc\Subject;
use App\Entity\Schedule\Shift;
use App\Entity\User\User;
use App\Form\SessionType;
use App\Entity\Misc\Semester;
use App\Entity\Misc\OperationHours;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/subject", name="admin_subject_")
 */
class SubjectController extends Controller {

    /**
     * @Route("/ajax/color", name="ajax_color")
     */
    public function ajaxColorAction(Request $request) {
        $subject = $this->getDoctrine()
            ->getRepository(Subject::class)
            ->find($request->request->get('subjectID'));

        $subject->setColor($request->request->get('subjectColor'));

        $em = $this->getDoctrine()
            ->getManager();

        $em->flush();

        return new Response('', Response::HTTP_OK);
    }

}