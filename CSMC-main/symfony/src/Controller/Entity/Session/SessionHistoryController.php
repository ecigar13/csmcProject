<?php

namespace App\Controller\Entity\Session;

use App\Entity\Session\Quiz;
use App\Entity\Session\QuizAttendance;
use App\Entity\Session\ScheduledSession;
use App\Entity\Session\ScheduledSessionAttendance;
use App\Entity\Session\Session;
use App\Entity\Session\SessionTimeSlot;
use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\TimeType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Session\Review;
use App\Entity\Session\Rework;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class SessionHistoryController extends Controller {
    /**
     * @Route("/session/history", name="session_history")
     */
    public function sessionHistoryAction() {
        if ($this->isGranted('mentor')) {
            //get all sessions that have been started
            $scheduledSessions = $this->getDoctrine()
                ->getRepository('App\Entity\Session\ScheduledSession')
                ->findAllPastAndCurrent();
            $quizzes = $this->getDoctrine()
                ->getRepository('App\Entity\Session\Quiz')
                ->findAllPastAndCurrent();


            $s = new ArrayCollection($scheduledSessions);
            $scheduledSessions = $s->filter(function ($session) {
                return $session->getEndDate() >= (new \DateTime())->setTime(12, 59, 59)
                        ->sub(new \DateInterval('P2D'));
            })->toArray();

            $q = new ArrayCollection($quizzes);
            $quizzes = $q->filter(function ($quiz) {
                return $quiz->getEndDate() >= (new \DateTime())->setTime(12, 59, 59)->sub(new \DateInterval('P2D'));
            })->toArray();

            return $this->render('role/mentor/session/history_by_time.html.twig', array(
                'sessions' => $scheduledSessions,
                'quizzes' => $quizzes
            ));
        } elseif ($this->isGranted('instructor')) {
            //get all sessions that have been done for courses taught by this faculty
            $user = $this->getUser();
            $scheduledSessions = $this->getDoctrine()
                ->getRepository('App\Entity\Session\ScheduledSession')
                ->findAllPastAndCurrentByInstructor($user);
            $quizzes = $this->getDoctrine()
                ->getRepository('App\Entity\Session\Quiz')
                ->findAllPastAndCurrentByInstructor($user);

            return $this->render('role/instructor/session/history_by_time.html.twig', array(
                'sessions' => $scheduledSessions,
                'quizzes' => $quizzes
            ));
        } elseif ($this->isGranted('student')) {
            //get all attendances to sessions
            $user = $this->getUser();
            /*
             * list of session attendance, by date, latest at top
             */
            $criteria = Criteria::create()
                ->where(Criteria::expr()->lte("timeIn", new \DateTime()))
                ->where(Criteria::expr()->eq("user", $user))
                ->orderBy(array("timeIn" => Criteria::DESC));

            $scheduled = $this->getDoctrine()
                ->getRepository('App\Entity\Session\ScheduledSessionAttendance')
                ->matching($criteria);
            $walkins = $this->getDoctrine()->getRepository('App\Entity\Session\WalkInAttendance')->matching(
                $criteria
            );
            $quizzes = $this->getDoctrine()->getRepository('App\Entity\Session\QuizAttendance')->matching(
                $criteria
            );

            return $this->render('role/student/session/history_by_time.html.twig', array(
                'sessions' => $scheduled,
                'quizzes' => $quizzes,
                'walkins' => $walkins
            ));
        } else {
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * @Route("/session/grades/{id}", name="session_grades")
     */
    public function sessionGradesAction(Request $request, $id) {
        $this->denyAccessUnlessGranted([
            'admin',
            'mentor',
            'instructor'
        ]);

        $session = $this->getDoctrine()->getRepository('App\Entity\Session\Session')->find($id);
        $type = get_class($session);
        if ($type == Quiz::class) {
            $attendances = $this->getDoctrine()->getRepository('App\Entity\Session\QuizAttendance')->findByQuiz(
                $session
            );
            if($this->isGranted('mentor')) {
                return $this->render('role/mentor/session/grades/grades.html.twig', array(
                    'session' => $session,
                    'attendees' => $attendances
                ));
            }elseif ($this->isGranted('instructor')) {
                return $this->render('role/instructor/session/grades/grades.html.twig', array(
                    'session' => $session,
                    'attendees' => $attendances
                ));
            }
        } elseif ($type == ScheduledSession::class) {
            $timeslots = $this->getDoctrine()
                ->getRepository(SessionTimeSlot::class)
                ->findBySession($session);
            if($this->isGranted('mentor')) {
                return $this->render('role/mentor/session/grades/grades.html.twig', array(
                    'session' => $session,
                    'timeslots' => $timeslots
                ));
            }elseif ($this->isGranted('instructor')) {
                return $this->render('role/instructor/session/grades/grades.html.twig', array(
                    'session' => $session,
                    'timeslots' => $timeslots
                ));
            }
        } else {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @Route("/session/grades/{id}/download", name="session_grades_download")
     */
    public function sessionGradesDownloadAction(Request $request, $id) {
        $this->denyAccessUnlessGranted([
            'admin',
            'instructor'
        ]);

        $session = $this->getDoctrine()->getRepository('App\Entity\Session\Session')->find($id);
        if (!$session) {
            throw $this->createNotFoundException();
        } else {
            $type = get_class($session);
            if ($type == Quiz::class) {
                $attendances = $this->getDoctrine()
                    ->getRepository('App\Entity\Session\QuizAttendance')
                    ->findByQuiz($session);
            } elseif ($type == ScheduledSession::class) {
                $timeslots = $this->getDoctrine()
                    ->getRepository(SessionTimeSlot::class)
                    ->findBySession($session);
                $attendances = array();
                foreach ($timeslots as $timeslot) {
                    $attendances = array_merge($attendances, $timeslot->getAttendances()->toArray());
                }
            }

            $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
            $normalizer = new ObjectNormalizer($classMetadataFactory);//, new AttendanceNameConverter());
            $timeCallback = function ($dateTime) {
                return $dateTime instanceof \DateTime
                    ? $dateTime->format('m/d/Y g:i A')
                    : '';
            };
            $normalizer->setCallbacks(array(
                'user' => function ($user) {
                    return $user instanceof User
                        ? $user->getLastName() . ':' . $user->getFirstName() . ':' . $user->getUsername()
                        : '';
                },
                'timeIn' => $timeCallback,
                'timeOut' => $timeCallback
            ));
            $encoder = new CsvEncoder();
            $serializer = new Serializer(array($normalizer), array($encoder));
            $report = $serializer->normalize($attendances, null, array('groups' => array('grade_report')));

            // TODO make this better and not as dumb
            for ($i = 0; $i < count($report); $i++) {
                $report[$i] = array(
                    'Last Name' => explode(':', $report[$i]['user'])[0],
                    'First Name' => explode(':', $report[$i]['user'])[1],
                    'Username' => explode(':', $report[$i]['user'])[2],
                    'Time In' => $report[$i]['timeIn'],
                    'Time Out' => $report[$i]['timeOut'],
                    'Grade' => $report[$i]['grade'],
                    'Comments' => $report[$i]['comments']
                );
            }

            $data = $serializer->serialize($report, 'csv');

            $response = new Response();
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                preg_replace("/[\\\/]/", "", $session->getTopic()) . ' ' . 'grades.csv'
            );
            $response->headers->set('Content-disposition', $disposition);
            $response->headers->set('Content-type', 'text/csv');
            $response->setContent($data);

            return $response;
            // return $this->render('test.html.twig', array(
            //     'data' => $data,
            //     'attendances' => $report
            // ));
        }
    }

    /**
     * @Route("/ajax/session/grades", name="session_grades_edit_ajax")
     */
    public function sessionGradesEditAjaxAction(Request $request) {
        $this->denyAccessUnlessGranted([
            'admin',
            'mentor',
            'developer'
        ]);

        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedHttpException([]);
        }

        $attendance_id = $request->request->get('attendance');
        $grade = $request->request->get('grade');

        $attendance = $this->getDoctrine()->getRepository('App\Entity\Session\Attendance')->find($attendance_id);

        if (!$attendance) {
            return new Response('', 404);
        }
        $attendance->setGrade($grade);
        $this->getDoctrine()->getManager()->flush();

        return (new JsonResponse(true));
    }

    /**
     * @Route("/attend/{id}", name="session_attend")
     */
    public function attendAction(\Symfony\Component\HttpFoundation\Request $request, Session $session) {
        $this->denyAccessUnlessGranted(['mentor']);

        $form = $this->createFormBuilder()
            ->add('user', EntityType::class, array(
                'class' => User::class,
                'choice_label' => function ($user) {
                    return $user->getFirstName() . ' ' . $user->getLastName() . ' (' . $user->getUsername() . ')';
                },
                'query_builder' => function (EntityRepository $er) use ($session) {
                    $students = $session->getStudents();
                    $qb = $er->createQueryBuilder('u');
                    $qb->where($qb->expr()->in('u', ':students'))
                        ->orderBy('u.firstName')
                        ->setParameters(array(
                            'students' => $students
                        ));
                    return $qb;
                },
            ));
        if ($session instanceof ScheduledSession) {
            $form->add('timeSlot', EntityType::class, array(
                'class' => SessionTimeSlot::class,
                'choice_label' => function ($timeslot) {
                    return $timeslot->getStartTime()->format('m/d h:i A');
                },
                'query_builder' => function (EntityRepository $er) use ($session) {
                    $qb = $er->createQueryBuilder('t')
                        ->where('t.session = :session')
                        ->orderBy('t.startTime')
                        ->setParameter('session', $session);

                    return $qb;
                }
            ));
        }
        $form->add('submit', SubmitType::class, array(
            'label' => 'Submit'
        ));

        $form = $form->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($session instanceof Quiz) {
                $attendance = new QuizAttendance($data['user'], $session->getTimeSlot());
            } else {
                $attendance = new ScheduledSessionAttendance($data['user'], $data['timeSlot']);
                $attendance->setTimeIn($data['timeSlot']->getStartTime());
                $attendance->setTimeOut($data['timeSlot']->getEndTime());
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($attendance);
            $entityManager->flush();

            $this->addFlash('success', 'Success');
            return $this->redirectToRoute('session_grades', array(
                'id' => $session->getId()
            ));
        }

        return $this->render('shared/form/form.html.twig', array(
            'form' => $form->createView()
        ));
    }
}