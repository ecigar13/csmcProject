<?php

namespace App\Controller\Entity\Schedule;

use App\Entity\Schedule\Absence;
use App\Entity\Schedule\AbsenceStatus;
use App\Entity\Schedule\ShiftAssignment;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Form\AbsenceType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use App\Utils\Utilities;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AbsenceController extends Controller {
    /**
     * @Route("/schedule/absence", name="absence")
     */
    public function absenceAction() {
        return $this->forward('App\Controller\Entity\Schedule\AbsenceController:absenceMarketAction');
    }

    /**
     * @Route("/schedule/absence/create", name="absence_create")
     */
    public function absenceCreateAction(Request $request) {
        $this->denyAccessUnlessGranted('mentor');

        $form = $this->createForm(AbsenceType::class, array(), array('user' => $this->getUser()));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $em = $this->getDoctrine()->getManager();

            $shift = $em->getRepository(ShiftAssignment::class)
                ->find($data['shift']);

            $absence = new Absence($shift, $data['reason']);

            $em->persist($absence);
            $em->flush();

            $this->addFlash('notice', 'Absence submitted successfully!');
            return $this->redirectToRoute('absence_market');
        }

        return $this->render('role/mentor/schedule/absence_form.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/schedule/absence/edit/{id}", name="absence_edit")
     */
    public function absenceEditAction(Request $request, Absence $absence) {
        $this->denyAccessUnlessGranted('mentor');

        // TODO do something better than comparing username
        if (!$absence || $absence->getAssignment()->getMentor()->getUsername() != $this->getUser()->getUsername()) {
            throw $this->createNotFoundException('Absence not found!');
        }

        $form = $this->createForm(AbsenceType::class, $absence, array('user' => $this->getUser()));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $absence = $form->getData();

            $em = $this->getDoctrine()->getManager();
            $em->persist($absence);
            $em->flush();

            $this->addFlash('notice', 'Absence updated successfully!');
            return $this->redirectToRoute('absence_market');
        }

        return $this->render('role/mentor/schedule/absence_market.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/schedule/absence/market", name="absence_market")
     */
    public function absenceMarketAction(Request $request) {
        $repo = $this->getDoctrine()->getRepository('App\Entity\Schedule\Absence');
        if ($this->isGranted(['mentor'])) {
            $your_absences = $repo->findAllUpcomingFor($this->getUser());
            $absences = $repo->findAllUpcomingExcluding($this->getUser());

            return $this->render('role/mentor/schedule/absence_market.html.twig', array(
                'your_absences' => $your_absences,
                'absences' => $absences
            ));
        } else {
            throw new AccessDeniedException();
        }
    }

    /**
     * @Route("/ajax/schedule/absence/cancel", name="absence_cancel_ajax")
     */
    public function absenceCancelAjaxAction(Request $request) {
        $this->denyAccessUnlessGranted('mentor');

        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedHttpException([]);
        }

        $absence_id = $request->request->get('absence');
        $absence = $this->getDoctrine()
            ->getRepository(Absence::class)
            ->find($absence_id);

        if (!$absence) {
            return new Response('', 404);
        }

        if (!empty($absence->getSubstitute())) {
            return new Response('', 409);
        }

        $absence->getAssignment()->setAbsence(null);

        $em = $this->getDoctrine()->getManager();
        $em->remove($absence);
        $em->flush();

        return new JsonResponse(true);
    }

    /**
     * @Route("/ajax/schedule/absence/market", name="absence_market_ajax")
     */
    public function absenceMarketAjaxAction(Request $request) {
        $this->denyAccessUnlessGranted('mentor');

        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedHttpException([]);
        }

        $absence_id = $request->request->get('absence');
        $absence = $this->getDoctrine()
            ->getRepository(Absence::class)
            ->find($absence_id);

        if (!$absence) {
            return new Response('', 404);
        }

        $assignment = new ShiftAssignment(
            $absence->getAssignment()->getScheduledShift(),
            $absence->getAssignment()->getSubject(),
            $this->getUser()
        );
        $absence->setSubstitute($assignment);

        $em = $this->getDoctrine()->getManager();
        $em->persist($assignment);
        $em->flush();

        return new JsonResponse(true);
    }
}