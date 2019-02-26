<?php

namespace App\Controller\Entity\Feedback;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Feedback\FeedbackType;
use App\Entity\Feedback\Feedback;
use App\Form\Feedback\FeedbackTypeType;

class FeedbackController extends Controller {

    /**
     * @Route("/feedback", name="feedback")
     */
    public function feedbackAction(Request $request) {
        $feedback = new Feedback();
        $form = $this->createForm(FeedbackType::class, $feedback);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $feedback = $form->getData();
            $feedback->setUser($this->getUser());
            $feedback->setPostDate(new \DateTime());
            $em = $this->getDoctrine()->getManager();
            $em->persist($feedback);
            $em->flush();

            $this->addFlash('notice', 'Thank you for your feedback!');
            return $this->redirectToRoute('home');
        }

        return $this->render('shared/form/form.html.twig', array(
            'user' => $this->getUser(),
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/feedback/type", name="feedback_type")
     */
    public function feedbackTypeAction(Request $request) {
        $this->denyAccessUnlessGranted(['admin', 'developer']);

        $feedbackType = new \App\Entity\Feedback\FeedbackType();
        $form = $this->createForm(FeedbackTypeType::class, $feedbackType);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $feedbackType = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($feedbackType);
            $em->flush();

            $this->addFlash('notice', 'Successfully created new feedback type "' . $feedbackType->getName() . '"');
            unset($feedbackType);
            unset($form);
            $feedbackType = new \App\Entity\Feedback\FeedbackType();
            $form = $this->createForm(FeedbackTypeType::class, $feedbackType);
        }

        return $this->render('shared/form/form.html.twig', array(
            'user' => $this->getUser(),
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/feedback/view", name="feedback_view")
     */
    public function feedbackViewAction(Request $request) {
        // TODO implement
    }
}