<?php

namespace App\Controller\Entity\Session;

use App\DataTransferObject\FileData;
use App\Entity\Misc\File;
use App\Entity\Session\RequestStatus;
use App\Form\RequestType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class SessionRequestController extends Controller {
    /**
     * @Route("/session/request", name="session_request")
     */
    public function sessionRequestAction() {
        if ($this->isGranted('instructor')) {
            $requests = $this->getDoctrine()
                ->getRepository(\App\Entity\Session\Request::class)
                ->findByUser($this->getUser());
        } else {
            throw $this->createAccessDeniedException();
        }

        return $this->render('role/instructor/session/request/requests.html.twig', array(
            'requests' => $requests
        ));
    }

    /**
     * @Route("/session/request/view/{id}", name="session_request_view")
     */
    public function sessionRequestViewAction(Request $request, $id) {
        $this->denyAccessUnlessGranted([
            'instructor'
        ]);

        $session_request = $this->getDoctrine()->getRepository('App\Entity\Session\Request')->find($id);
        if (!$session_request) {
            throw new $this->createNotFoundException('Request not found!');
        } else {
            if ($session_request->getUser() != $this->getUser()) {
                throw $this->createAccessDeniedException();
            }

            return $this->render('role/instructor/session/request/request.html.twig', array(
                'request' => $session_request
            ));
        }
    }

    /**
     * @Route("/session/request/create", name="session_request_create")
     */
    public function sessionRequestCreateAction(Request $request) {
        $this->denyAccessUnlessGranted([
            'instructor'
        ]);

        $form = $this->createForm(RequestType::class, array(), array('user' => $this->getUser()));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $session_request_data = $form->getData();

            $session_request = new \App\Entity\Session\Request($session_request_data['type'],
                $this->getUser(),
                $session_request_data['topic'],
                $session_request_data['startDate'],
                $session_request_data['endDate'],
                $session_request_data['studentInstructions'],
                $session_request_data['sections']->toArray());

            foreach($session_request_data['files'] as $file) {
                $file_data = new FileData($file, $this->getUser());
                $session_request->attachFile($file_data, $em);
            }


            $em->persist($session_request);

            $em->flush();


            $this->addFlash('notice', 'Successfully requested session!');

            return $this->redirectToRoute('session_request');
        }

        return $this->render('role/instructor/session/request/form.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/session/request/edit/{id}", name="session_request_edit")
     */
    public function sessionRequestEdit(Request $request, \App\Entity\Session\Request $session_request) {
        $this->denyAccessUnlessGranted([
            'instructor'
        ]);

        $data = array(
            'topic' => $session_request->getTopic(),
            'startDate' => $session_request->getStartDate(),
            'endDate' => $session_request->getEndDate(),
            'studentInstructions' => $session_request->getStudentInstructions(),
            'sections' => $session_request->getSections(),
            'uploadedFiles' => $session_request->getFiles()
        );

        $form = $this->createForm(RequestType::class, $data, array('user' => $session_request->getUser()));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $session_request_data = $form->getData();
            $em = $this->getDoctrine()->getManager();

            $session_request->update($session_request_data['type'],
                $session_request_data['topic'],
                $session_request_data['startDate'],
                $session_request_data['endDate'],
                $session_request_data['studentInstructions'],
                $session_request_data['sections']->toArray());

            foreach ($session_request_data['files'] as $file) {
                $file_data = new FileData($file, $this->getUser());
                $session_request->attachFile($file_data, $em);
            }

            $em->flush();

            $this->addFlash('notice', 'Successfully requested session!');


            return $this->redirectToRoute('session_request');
        }

        return $this->render('role/instructor/session/request/form.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
