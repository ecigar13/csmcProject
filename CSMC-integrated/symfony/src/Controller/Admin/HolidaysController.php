<?php

namespace App\Controller\Admin;

use App\Entity\Misc\Holiday;
use DoctrineExtensions\Query\Mysql\Date;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * @Route("/admin/holiday", name="admin_holiday_")
 */
class HolidaysController extends Controller {
    /**
     * @Route("/create", name="create")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addHolidaysAction(Request $request) {
        $holidays = new Holiday();
        $holidays->setLastModifiedOn(new DateType());
        $holidays->setCreatedOn(new DateType());
        $form = $this->createFormBuilder($holidays)
            ->add('holidayDate', DateType::class, array(
                'error_bubbling' => true,
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => 'yyyy/mm/dd'
            ))
            ->add('StartTime', TimeType::class, array(
                'label' => 'Start Time',
                'html5' => true,
                'widget' => 'single_text',
                'placeholder' => 'hh:mm'
            ))
            ->add("EndTime", TimeType::class, array(
                'label' => 'End Time',
                'html5' => true,
                'widget' => 'single_text',
                'placeholder' => 'hh:mm'
            ))
            ->add('closed', CheckboxType::class, array(
                'label' => 'Closed',
                'required' => false,
            ))
            ->add("description", TextareaType::class, array(
                'required' => false,
                'error_bubbling' => true
            ))
            ->add('submit', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $holidays->setDay($form['holidayDate']->getData()->format("l"));
            $holidays = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($holidays);
            $entityManager->flush();
            return $this->redirectToRoute('admin_holiday_list');
        }
        return $this->render('role/admin/holiday/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/", name="list")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listHolidaysFunction(Request $request) {
        $holidays = $this->getDoctrine()
            ->getRepository(Holiday::class)
            ->findAll();
        return $this->render('role/admin/holiday/list.html.twig', array(
            'holidays' => $holidays
        ));
    }

    /**
     * @Route("/edit", name="edit")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editHolidayFunction(Request $request) {
        $delete_id = $request->query->get('id');
        $entityManager = $this->getDoctrine()->getManager();
        $holiday = $entityManager->getRepository(Holiday::class)->find($delete_id);
        $holidayName = $holiday->getDescription();
        if (!$delete_id) {
            throw $this->createNotFoundException(
                'No Holiday found for id ' . $delete_id
            );
        }
        $form = $this->createFormBuilder($holiday)
            ->add('holidayDate', DateType::class, array(
                'error_bubbling' => true,
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => 'yyyy/mm/dd'
            ))
            ->add('StartTime', TimeType::class, array(
                'label' => 'Start Time',
                'html5' => true,
                'widget' => 'single_text',
                'placeholder' => 'hh:mm'
            ))
            ->add("EndTime", TimeType::class, array(
                'label' => 'End Time',
                'html5' => true,
                'widget' => 'single_text',
                'placeholder' => 'hh:mm'
            ))
            ->add('closed', CheckboxType::class, array(
                'label' => 'Closed',
                'required' => false,
            ))
            ->add("description", TextareaType::class, array(
                'required' => false,
                'error_bubbling' => true,
                'attr' => array('style' => 'font-weight:200;')
            ))
            ->add('submit', SubmitType::class, array('label' => 'Save'))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $holiday->setLastModifiedOn(new DateType());
            $holiday->setDay($form['holidayDate']->getData()->format("l"));
            $entityManager->flush();

            return $this->redirectToRoute('admin_holiday_list');
        }
        return $this->render('role/admin/holiday/edit.html.twig', array(
            'form' => $form->createView(),
            'deleteId' => $delete_id,
            'holidayName' => $holidayName
        ));
    }

    /**
     * @Route("/delete", name="delete")
     */
    public function deleteHolidayFunction(Request $request) {
        $id = $request->query->get('id');
        $entityManager = $this->getDoctrine()->getManager();
        $holiday = $entityManager->getRepository(Holiday::class)->find($id);
        if (!$id) {
            throw $this->createNotFoundException(
                'No Holiday found for id ' . $id
            );
        }
        $entityManager->remove($holiday);
        $entityManager->flush();

        return $this->redirectToRoute('admin_holiday_list');
    }
}
