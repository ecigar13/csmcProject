<?php

namespace App\Controller\Entity\Session;

use App\Entity\Course\Section;
use App\Entity\Session\Quiz;
use App\Entity\Session\Review;
use App\Entity\Session\Rework;
use App\Entity\Session\ScheduledSession;
use App\Entity\Session\ScheduledSessionAttendance;
use App\Entity\Session\Session;
use App\Entity\Session\SessionTimeSlot;
use App\Entity\Session\TimeSlot;

// use App\Form\Session\QuizAttendanceType;
// use App\Form\Session\ScheduledSessionAttendanceType;
// use App\Serializer\Converter\UserNameConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use App\Entity\Misc\File;

class SessionController extends Controller {
    /**
     * @Route("/session/{month}/{year} ", name="session", requirements={"month": "\d{2}", "year": "\d{4}"})
     */
    public function sessionAction($month = null, $year = null) {
        return $this->forward('App:Entity/Session/Session:sessionSchedule');
    }

    /**
     * @Route("/session/view/{id}", name="session_view")
     */
    public function sessionViewAction(Session $session) {

        if (!$session) {
            throw $this->createNotFoundException('Session was not found');
        } else {
            if ($this->isGranted('mentor')) {
                return $this->render('role/mentor/session/session.html.twig', array(
                    'session' => $session
                ));
            } elseif ($this->isGranted('instructor')) {
                return $this->render('role/instructor/session/session.html.twig', array(
                    'session' => $session
                ));
            } else {
                return $this->createAccessDeniedException();
            }
        }
    }

    /**
     * @Route("/session/timeslot/view/{tid}", name="session_timeslot_view")
     */
    public function sessionTimeslotViewAction($tid) {
        $this->denyAccessUnlessGranted([
            'mentor'
        ]);

        $timeslot = $this->getDoctrine()->getRepository(TimeSlot::class)->find($tid);

        if (!$timeslot) {
            throw $this->createNotFoundException('Time slot with id ' . $tid . ' was not found');
        } else {
            return $this->render('role/mentor/session/timeslot.html.twig', array(
                'timeslot' => $timeslot
            ));
        }
    }

    // /**
    //  * @Route("/session/timeslot/view/{tid}/roster", name="session_timeslot_roster")
    //  */
    // public function sessionTimeslotRosterAction($tid) {
    //     $this->denyAccessUnlessGranted([
    //         'admin',
    //         'session',
    //         'mentor',
    //         'instructor'
    //     ]);
    //
    //     $timeslot = $this->getDoctrine()->getRepository(TimeSlot::class)->find($tid);
    //
    //     if (!$timeslot) {
    //         throw $this->createNotFoundException('Time slot with id ' . $tid . ' was not found');
    //     } else {
    //         $registrations = $timeslot->getRegistrations();
    //         $roster = array();
    //         foreach ($registrations as $reg) {
    //             $roster[] = $reg->getUser();
    //         }
    //
    //         $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
    //         $normalizer = new ObjectNormalizer($classMetadataFactory, new UserNameConverter());
    //         $encoder = new CsvEncoder();
    //         $serializer = new Serializer(array($normalizer), array($encoder));
    //         $roster = $serializer->normalize($roster, null, array('groups' => array('roster')));
    //         $data = $serializer->serialize($roster, 'csv');
    //
    //         $response = new Response();
    //         $disposition = $response->headers->makeDisposition(
    //             ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    //             'roster.csv'
    //         );
    //         $response->headers->set('Content-disposition', $disposition);
    //         $response->headers->set('Content-type', 'text/csv');
    //         $response->setContent($data);
    //
    //         return $response;
    //     }
    // }

    /**
     * @Route("/session/schedule", name="session_schedule")
     */
    public function sessionScheduleAction() {
        if ($this->isGranted('mentor')) {
            $timeslots = $this->getDoctrine()
                ->getRepository(SessionTimeSlot::class)
                ->findAllFutureAndCurrent();
                // ->findAll();
            $quizzes = $this->getDoctrine()
                ->getRepository(Quiz::class)
                ->findAllFutureAndCurrent();
                // ->findAll();
            return $this->render('role/mentor/session/schedule_by_time.html.twig', array(
                'timeslots' => $timeslots,
                'quizzes' => $quizzes
            ));
        } elseif ($this->isGranted('instructor') || $this->isGranted('teaching_assistant')) {
            $courses = array();
            $sections = $this->getDoctrine()
                ->getRepository('App\Entity\Course\Section')
                ->findByInstructor($this->getUser());
            foreach ($sections as $section) {
                $courses[] = array(
                    'section' => $section,
                    'quizzes' => $this->getDoctrine()
                        ->getRepository('App\Entity\Session\Quiz')
                        ->findAllFutureAndCurrentBySection($section),
                    'sessions' => $this->getDoctrine()
                        ->getRepository('App\Entity\Session\ScheduledSession')
                        ->findAllFutureAndCurrentBySection($section)
                );
            }

            return $this->render('role/instructor/session/schedule_by_course.html.twig', array(
                'courses' => $courses
            ));
        } elseif ($this->isGranted('student')) {
            $courses = array();
            $sections = $this->getDoctrine()
                ->getRepository(Section::class)
                ->findAllByStudent($this->getUser());
            foreach ($sections as $section) {
                $courses[] = array(
                    'section' => $section,
                    'quizzes' => $this->getDoctrine()
                        ->getRepository(Quiz::class)
                        ->findAllFutureAndCurrentBySection($section),
                    'sessions' => $this->getDoctrine()
                        ->getRepository(ScheduledSession::class)
                        ->findAllFutureAndCurrentBySection($section)
                );
            }

            return $this->render('role/student/session/schedule_by_course.html.twig', array(
                'courses' => $courses
            ));
        } else {
            throw $this->createAccessDeniedException();
        }
    }


    // /**
    //  * @Route("/session/attendance/create/scheduled/{id}", name="session_attendance_create_scheduled")
    //  */
    // public function sessionScheduledAttendanceCreateAction(Request $request, $id) {
    //     $this->denyAccessUnlessGranted('admin');
    //
    //     $timeslot = $this->getDoctrine()
    //         ->getRepository(TimeSlot::class)
    //         ->find($id);
    //
    //     $attendance = new ScheduledSessionAttendance();
    //     $attendance->setTimeSlot($timeslot);
    //     $form = $this->createForm(ScheduledSessionAttendanceType::class, $attendance);
    //
    //     $form->handleRequest($request);
    //
    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $attendance = $form->getData();
    //
    //         $em = $this->getDoctrine()->getManager();
    //         $em->persist($attendance);
    //         $em->flush();
    //
    //         $this->addFlash('notice', $attendance->getUser() . ' marked as attended');
    //         return $this->redirectToRoute('session_grades', array('id' => $timeslot->getSession()->getId()));
    //     }
    //
    //     return $this->render('shared/form/form.html.twig', array(
    //         'form' => $form->createView()
    //     ));
    // }
}