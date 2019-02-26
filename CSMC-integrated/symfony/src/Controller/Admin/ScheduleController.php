<?php

namespace App\Controller\Admin;

use App\Entity\Misc\Subject;
use App\Entity\Schedule\Absence;
use App\Entity\Schedule\Schedule;
use App\Entity\Schedule\ScheduledShift;
use App\Entity\Schedule\Shift;
use App\Entity\Schedule\Timesheet;
use App\Entity\User\User;
use App\Form\SessionType;
use App\Entity\Misc\Semester;
use App\Entity\Misc\OperationHours;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/schedule", name="admin_schedule_")
 */
class ScheduleController extends Controller {
    /**
     * @Route("/calendar", name="calendar")
     */
    public function calendarAction() {
        $hours = $this->getDoctrine()
            ->getRepository(OperationHours::class)
            ->findAll();

        $mentors = $this->getDoctrine()
            ->getRepository(User::class)
            ->findByRole("mentor");

        usort($mentors, array("App\Entity\User\User", "compareMentors"));

        $semester = $this->getDoctrine()
            ->getRepository(Semester::class)
            ->findActive();

        $schedule = $semester->getSchedule();

        $subjects = $this->getDoctrine()
            ->getRepository(Subject::class)
            ->findByShowOnCalendar(true);

        return $this->render('role/admin/schedule/calendar.html.twig', array(
            'mentors' => $mentors,
            'schedule' => $schedule,
            'hours' => $hours,
            'subjects' => $subjects
        ));
    }

    /**
     * @Route("/absences", name="absences")
     */
    public function absencesAction() {
        $absences = $this->getDoctrine()
            ->getRepository(Absence::class)
            ->findAllUpcoming();

        return $this->render('role/admin/schedule/absences.html.twig', array(
            'absences' => $absences
        ));
    }

    /**
     * @Route("/timesheets", name="timesheets")
     */
    public function timesheetsAction(Request $request) {
        $form = $this->createFormBuilder()
            ->add('mentor', EntityType::class, array(
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('u')
                        ->join('u.roles', 'r')
                        ->where('r.name = :role')
                        ->setParameter('role', 'mentor');
                    return $qb;
                },
                'placeholder' => ''
            ))->add('start', DateType::class, array(
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => 'yyyy/mm/dd'
            ))->add('end', DateType::class, array(
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => 'yyyy/mm/dd'
            ))->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        $timesheet = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $times = $this->getDoctrine()
                ->getRepository(Timesheet::class)
                ->findByUserAndDates($data['mentor'], $data['start'], $data['end']);

            $timesheet = array(
                'mentor' => $data['mentor'],
                'start' => $data['start'],
                'end' => $data['end'],
                'times' => $times
            );
        }

        return $this->render('role/admin/schedule/timesheets.html.twig', array(
            'timesheet' => $timesheet,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/update", name="update")
     */
    public function updateAction() {
        $semester = $this->getDoctrine()
            ->getRepository(Semester::class)
            ->findActive();

        $schedule = $semester->getSchedule();

        $period = new \DatePeriod(new \DateTime(), new \DateInterval('P1D'), $semester->getEndDate());
        foreach ($period as $date) {
            if ($date < (new \DateTime())->setTime(0, 0, 0)) {
                continue;
            }

            foreach ($schedule->getShifts() as $shift) {
                if ($date->format('w') == $shift->getDay()) {
                    $scheduled_shift = $this->getDoctrine()
                        ->getRepository(ScheduledShift::class)
                        ->findOneBy(array(
                            'schedule' => $schedule,
                            'shift' => $shift,
                            'date' => $date
                        ));

                    if ($scheduled_shift == null) {
                        $scheduled_shift = new ScheduledShift($schedule, $shift, $date);

                        $this->getDoctrine()
                            ->getManager()
                            ->persist($scheduled_shift);
                    } else {
                        $need_deleted = $scheduled_shift->updateAssignments();
                        foreach($need_deleted as $unneeded) {
                            if($unneeded->getAbsence()) {
                                continue;
                            }

                            if($this->getDoctrine()->getRepository(Absence::class)->findBySubstitute($unneeded)) {
                                continue;
                            }

                            $unneeded->setSession(null);

                            $this->getDoctrine()
                                ->getManager()
                                ->remove($unneeded);
                        }
                    }
                }
            }
        }

        $this->getDoctrine()
            ->getManager()
            ->flush();

        return $this->redirectToRoute('admin_schedule_calendar');
    }

    /**
     * @Route("/ajax/shift", name="ajax_shift")
     */
    public function ajaxShiftAction(Request $request) {
        $mentor = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($request->request->get('mentorID'));

        $shift = $this->getDoctrine()
            ->getRepository(Shift::class)
            ->find($request->request->get('shiftID'));

        $subject = $this->getDoctrine()
            ->getRepository(Subject::class)
            ->find($request->request->get('subjectID'));

        $shift->addMentor($subject, $mentor);

        $em = $this->getDoctrine()
            ->getManager();

        $em->flush();

        return new Response('', Response::HTTP_OK);
    }

    /**
     * @Route("/ajax/shift/remove", name="ajax_shift_remove")
     */
    public function ajaxShiftRemoveAction(Request $request) {
        $mentor = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($request->request->get('mentorID'));

        $shift = $this->getDoctrine()
            ->getRepository(Shift::class)
            ->find($request->request->get('shiftID'));

        $subject = $this->getDoctrine()
            ->getRepository(Subject::class)
            ->find($request->request->get('subjectID'));

        $shift->removeMentor($subject, $mentor);

        $em = $this->getDoctrine()
            ->getManager();

        $em->flush();

        return new Response('', Response::HTTP_OK);
    }

    /**
     * @Route("/ajax/shift/leader", name="ajax_shift_leader")
     */
    public function ajaxShiftLeaderAction(Request $request) {
        $shift = $this->getDoctrine()
            ->getRepository(Shift::class)
            ->find($request->request->get('shiftID'));

        $mentor = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($request->request->get('mentorID'));

        $shift->assignShiftLeader($mentor);

        $em = $this->getDoctrine()
            ->getManager();

        $em->flush();

        return new Response('', Response::HTTP_OK);
    }

}