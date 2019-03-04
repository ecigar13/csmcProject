<?php

namespace App\Controller\Admin;

use App\Entity\Misc\OperationHours;
use DoctrineExtensions\Query\Mysql\Time;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * @Route("/admin/hours", name="admin_hours_")
 */
class OperationHoursController extends Controller {
    /**
     * @Route("/", name="list")
     */
    public function listOperationHours() {
        $entityManager = $this->getDoctrine()->getManager();
        $operation_hours = $this->getDoctrine()
            ->getRepository(OperationHours::class)
            ->findAll();
        return $this->render('role/admin/schedule/listOperationHours.html.twig', array(
            'operationHours' => $operation_hours
        ));
    }

    /**
     * @Route("/edit", name="edit")
     */
    public function editOperationHours(Request $request) {
        $edit_id = $request->query->get('id');
        $entityManager = $this->getDoctrine()->getManager();
        $operation_hours = $entityManager->getRepository(OperationHours::class)->find($edit_id);
        $operationDay = $operation_hours->getDay();
        $form = $this->createFormBuilder($operation_hours)
            ->add('Day', TextType::class, array('disabled' => true))
            ->add('StartTime', TimeType::class, array(
                'html5' => true,
                'widget' => 'single_text',
                'placeholder' => 'hh:mm'
            ))
            ->add("EndTime", TimeType::class, array(
                'html5' => true,
                'widget' => 'single_text',
                'placeholder' => 'hh:mm'
            ))
            ->add('Submit', SubmitType::class, array('label' => 'Save'))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $operation_hours->setLastModifiedOn(new DateType());
            $entityManager->flush();

            return $this->redirectToRoute('admin_hours_list');
        }
        return $this->render('role/admin/schedule/editOperationHours.html.twig', array(
            'form' => $form->createView(),
            'operationDay' => $operationDay
        ));
    }
}
