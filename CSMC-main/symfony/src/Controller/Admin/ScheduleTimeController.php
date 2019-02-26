<?php

namespace App\Controller\Admin;

use App\Entity\Schedule\ScheduleTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @Route("/admin/schedule", name="admin_schedule_")
 */
class ScheduleTimeController extends Controller {
    /**
     * @Route("/", name="list")
     */
    public function listScheduleTime() {
        $entityManager = $this->getDoctrine()->getManager();
        $schedule_time = $this->getDoctrine()
            ->getRepository(ScheduleTime::class)
            ->findAll();
        return $this->render('role/admin/schedule/listScheduleTimes.html.twig', array('schedule_time' => $schedule_time));
    }

    /**
     * @Route("/edit", name="edit")
     */
    public function editScheduleTime(Request $request) {
        $edit_id = $request->query->get('id');
        $entityManager = $this->getDoctrine()->getManager();
        $schedule_times = $entityManager->getRepository(ScheduleTime::class)->find($edit_id);
        $form = $this->createFormBuilder($schedule_times)
            ->add('StartDate', DateType::class, array(
                'label' => 'Start Date',
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => 'yyyy/mm/dd'
            ))
            ->add('EndDate', DateType::class, array(
                'label' => 'End Date',
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => 'yyyy/mm/dd'
            ))
            ->add('StartOfWeek', ChoiceType::class, array(
                'choices' => array(
                    'Sunday' => 'Sunday',
                    'Monday' => 'Monday',
                    'Tuesday' => 'Tuesday',
                    'Thursday' => 'Thursday',
                    'Friday' => 'Friday',
                    'Saturday' => 'Saturday'
                ),
                'label' => 'Start Of Week'
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
            ->add('Submit', SubmitType::class, array('label' => 'Save'))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //  $schedule_times->setLastModifiedOn(new DateType());
            $entityManager->persist($schedule_times);
            $entityManager->flush();

            return $this->redirectToRoute('admin_schedule_list');
        }
        return $this->render('role/admin/schedule/editScheduleTime.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
