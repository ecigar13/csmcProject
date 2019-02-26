<?php

namespace App\Controller\Admin;

use App\DataTransferObject\FileData;
use App\Entity\Misc\OperationHours;
use App\Entity\Misc\Room;
use App\Entity\Schedule\ShiftAssignment;
use App\Entity\Session\Quiz;
use App\Entity\Session\QuizAttendance;
use App\Entity\Session\Registration;
use App\Entity\Session\Request;
use App\Entity\Session\RequestStatus;
use App\Entity\Session\ScheduledSession;
use App\Entity\Session\ScheduledSessionAttendance;
use App\Entity\Session\Session;
use App\Entity\Session\SessionTimeSlot;
use App\Entity\Session\TimeSlot;
use App\Entity\User\User;
use App\Form\QuizType;
use App\Form\RequestType;
use App\Form\ScheduledSessionType;
use App\Form\SessionType;
use App\Form\TimeSlotType;
use App\Utils\Serializer;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/admin/session", name="admin_session_")
 */
class SessionController extends Controller {
    /**
     * @Route("/calendar", name="calendar")
     */
    public function calendarAction() {
        $hours = $this->getDoctrine()
            ->getRepository(OperationHours::class)
            ->findAll();

        $sessions = $this->getDoctrine()
            ->getRepository(ScheduledSession::class)
            ->findAllUnscheduled();

        $session_form = $this->createForm(ScheduledSessionType::class, array(), array(
            'action' => $this->generateUrl('admin_session_create_scheduled')
        ));

        $quiz_form = $this->createForm(QuizType::class, array(), array(
            'action' => $this->generateUrl('admin_session_create_quiz')
        ));

        $time_slot_form = $this->createForm(TimeSlotType::class, array(), array(
            'action' => $this->generateUrl('admin_session_edit_time_slot')
        ));

        return $this->render('role/admin/session/calendar.html.twig', array(
            'hours' => $hours,
            'sessions' => $sessions,
            'session_form' => $session_form->createView(),
            'quiz_form' => $quiz_form->createView(),
            'time_slot_form' => $time_slot_form->createView()
        ));
    }

    /**
     * @Route("/create/scheduled", name="create_scheduled")
     */
    public function createScheduledSessionAction(\Symfony\Component\HttpFoundation\Request $request) {
        $form = $this->createForm(ScheduledSessionType::class);
        $form->submit($request->request->get('scheduled_session'));

        if ($form->isSubmitted() && $form->isValid()) {
            $session_data = $form->getData();

            $em = $this->getDoctrine()
                ->getManager();

            $session = ScheduledSession::createFromFormData($session_data);
            if ($session_data['request'] != null) {
                $session_request = $em->getRepository(Request::class)
                    ->find($session_data['request']);
                $session->setRequest($session_request);
                $session_request->setStatus('pending');

                foreach ($session_request->getFiles() as $file) {
                    $session->attachExistingFile($file);
                }
            }

            foreach ($session_data['files'] as $file) {
                $file_data = new FileData($file, $this->getUser());
                $session->attachFile($file_data, $em);
            }

            $em->persist($session);
            $em->flush();

            return $this->redirectToRoute('admin_session_calendar');
        }

        return new Response('', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/edit/scheduled/{id}", name="edit_scheduled")
     */
    public function editScheduledSessionAction(\Symfony\Component\HttpFoundation\Request $request, ScheduledSession $session) {
        $data = array();
        $form = $this->createForm(ScheduledSessionType::class);
        $form->submit($request->request->get('scheduled_session'));

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $em = $this->getDoctrine()
                ->getManager();

            $session->update($data['type'], $data['topic'], $data['description'], $data['studentInstructions'],
                $data['mentorInstructions'], $data['graded'], $data['numericGrade']);

            $session->setRepeats($data['repeats']);
            $session->setDefaults($data['defaultLocation'], $data['defaultCapacity'], $data['defaultDuration']);

            foreach ($session->getFiles() as $file) {
                $found = false;
                foreach ($data['uploadedFiles'] as $uploadedFile) {
                    if ($file->getId() == $uploadedFile->getId()) {
                        $found = true;
                    }
                }
                if (!$found) {
                    $session->detachFile($file);
                }
            }

            foreach ($data['files'] as $file) {
                $file_data = new FileData($file, $this->getUser());
                $session->attachFile($file_data, $em);
            }

            $em->flush();

            return $this->redirectToRoute('admin_session_calendar');
        }

        return new Response('', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/create/quiz", name="create_quiz")
     */
    public function createQuizAction(\Symfony\Component\HttpFoundation\Request $request) {
        $form = $this->createForm(QuizType::class);
        $form->submit($request->request->get('quiz'));

        if ($form->isSubmitted() && $form->isValid()) {
            $quiz_data = $form->getData();

            $em = $this->getDoctrine()
                ->getManager();

            $quiz = Quiz::createFromFormData($quiz_data);
            if ($quiz_data['request'] != null) {
                $session_request = $em->getRepository(Request::class)
                    ->find($quiz_data['request']);
                $quiz->setRequest($session_request);
                $session_request->setStatus('completed');
            }

            foreach ($quiz_data['files'] as $file) {
                $file_data = new FileData($file, $this->getUser());
                $quiz->attachFile($file_data, $em);
            }

            $em->persist($quiz);
            $em->flush();

            return $this->redirectToRoute('admin_session_calendar');
        }

        return new Response('', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/edit/quiz/{id}", name="edit_quiz")
     */
    public function editQuizAction(\Symfony\Component\HttpFoundation\Request $request, Quiz $quiz) {
        $form = $this->createForm(QuizType::class);
        $form->submit($request->request->get('quiz'));

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $em = $this->getDoctrine()
                ->getManager();

            $quiz->update($data['type'], $data['topic'], $data['description'], $data['studentInstructions'],
                $data['mentorInstructions'], $data['graded'], $data['numericGrade']);

            $quiz->updateDates($data['startDate'], $data['endDate']);
            $quiz->updateLocation($data['room']);

            foreach ($data['files'] as $file) {
                $file_data = new FileData($file, $this->getUser());
                $quiz->attachFile($file_data, $em);
            }

            $em->flush();

            return $this->redirectToRoute('admin_session_calendar');
        }
    }

    /**
     * @Route("/create/timeslot", name="create_time_slot")
     */
    public function createTimeSlotAction(\Symfony\Component\HttpFoundation\Request $request) {
        $form = $this->createForm(TimeSlotType::class);
        $form->submit($request->request->get('time_slot'));

        if ($form->isSubmitted() && $form->isValid()) {
            $time_slot_data = $form->getData();

            $time_slot_data['start'] = new \DateTime($time_slot_data['start']);
            $time_slot_data['end'] = new \DateTime($time_slot_data['end']);

            $time_slot_data['session'] = $this->getDoctrine()
                ->getRepository(Session::class)
                ->find($time_slot_data['session']);

            $time_slot_data['location'] = $this->getDoctrine()
                ->getRepository(Room::class)
                ->find($time_slot_data['location']);

            $ts = SessionTimeSlot::createFromFormData($time_slot_data);

            $request = $ts->getSession()->getRequest();
            if ($request != null) {
                $request->setStatus('completed');
            }

            $em = $this->getDoctrine()
                ->getManager();

            $em->persist($ts);
            $em->flush();

            return new Response($ts->getId(), Response::HTTP_OK);
        }

        return new Response('', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/edit/timeslot", name="edit_time_slot")
     */
    public function editTimeSlotAction(\Symfony\Component\HttpFoundation\Request $request) {
        $time_slot_data = $request->request->get('time_slot');
        $id = $request->request->get('id') ?: $time_slot_data['id'];

        $time_slot = $this->getDoctrine()
            ->getRepository(SessionTimeSlot::class)
            ->find($id);

        if (isset($time_slot_data['date'])) {
            $start = new \DateTime($time_slot_data['date'] . ' ' . $time_slot_data['startTime']);
            $end = new \DateTime($time_slot_data['date'] . ' ' . $time_slot_data['endTime']);
        } else {
            $start = new \DateTime($time_slot_data['start']);
            $end = new \DateTime($time_slot_data['end']);
        }

        $time_changed = false;
        if ($time_slot->getStartTime() != $start || $time_slot->getEndTime() != $end) {
            $time_changed = true;
        }

        $time_slot->updateTime($start, $end);
        $time_slot->updateCapacity($time_slot_data['capacity']);
        $time_slot->updateLocation($this->getDoctrine()
            ->getRepository(Room::class)
            ->find($time_slot_data['location']));

        if ($time_changed) {
            foreach ($time_slot->getAssignments() as $assignment) {
                $time_slot->unassign($assignment->getMentor());
            }
        }

        if (isset($time_slot_data['assignments'])) {
            foreach ($time_slot_data['assignments'] as $mentor) {
                $time_slot->assign($this->getDoctrine()
                    ->getRepository(ShiftAssignment::class)
                    ->find($mentor));
            }

            foreach ($time_slot->getAssignments() as $assignment) {
                $assigned = false;
                foreach ($time_slot_data['assignments'] as $assignee) {
                    $this->get('logger')->info('checking against: ' . $assignee);
                    if ($assignee == $assignment->getId()) {
                        $assigned = true;
                    }
                }

                if (!$assigned) {
                    $time_slot->unassign($assignment->getMentor());
                }
            }
        }


        $em = $this->getDoctrine()
            ->getManager();

        $em->flush();

        return new Response('', Response::HTTP_OK);
    }

    /**
     * @Route("/request/edit/{id}", name="session_request_edit")
     */
    public function sessionRequestEditAction(\Symfony\Component\HttpFoundation\Request $request, Request $session_request) {
        $data = array(
            'topic' => $session_request->getTopic(),
            'startDate' => $session_request->getStartDate(),
            'endDate' => $session_request->getEndDate(),
            'studentInstructions' => $session_request->getStudentInstructions(),
            'sections' => $session_request->getSections(),
            // 'uploadedFiles' => $session_request->getFiles()
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

            return $this->redirectToRoute('admin_session_requests');
        }

        return $this->render('role/admin/session/request_form.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/assignments", name="assignments")
     */
    public function assignmentsFeedAction(\Symfony\Component\HttpFoundation\Request $request, Serializer $serializer) {
        $date = $request->query->get('date');
        $time = $request->query->get('time');

        $assignments = $this->getDoctrine()
            ->getRepository(ShiftAssignment::class)
            ->findForTimes(new \DateTime($date), new \DateTime($time));

        $callback = function ($mentor) {
            return $mentor instanceof User
                ? $mentor->getPreferredName()
                : '';
        };

        $data = $serializer->serialize($assignments, array(
            'attributes' => [
                'id',
                'mentor'
            ],
            'callbacks' => [
                'mentor' => $callback
            ]
        ));

        return JsonResponse::fromJsonString($data);
    }

    /**
     * @Route("/requests", name="requests")
     */
    public function requestsAction() {
        return $this->render('role/admin/session/requests.html.twig');
    }

    /**
     * @Route("/create", name="create")
     */
    public function sessionCreateAction(\Symfony\Component\HttpFoundation\Request $request) {
        $session_form = $this->createForm(ScheduledSessionType::class, array(), array(
            'action' => $this->generateUrl('admin_session_create_scheduled')
        ));

        $quiz_form = $this->createForm(QuizType::class, array(), array(
            'action' => $this->generateUrl('admin_session_create_quiz')
        ));

        return $this->render('role/admin/session/form.html.twig', array(
            'session_form' => $session_form->createView(),
            'quiz_form' => $quiz_form->createView()
        ));
    }

    /**
     * @Route("/create/{id}", name="create_from_request")
     */
    public function sessionCreateFromRequestAction(\Symfony\Component\HttpFoundation\Request $request, Request $session_request) {
        $data = array(
            'topic' => $session_request->getTopic(),
            'type' => $session_request->getType(),
            'studentInstructions' => $session_request->getStudentInstructions(),
            'uploadedFiles' => $session_request->getFiles()->toArray(),
            'sections' => $session_request->getSections(),
            'request' => $session_request->getId()
        );

        $session_form = $this->createForm(ScheduledSessionType::class, $data, array(
            'action' => $this->generateUrl('admin_session_create_scheduled')
        ));

        $quiz_form = $this->createForm(QuizType::class, $data, array(
            'action' => $this->generateUrl('admin_session_create_quiz')
        ));

        return $this->render('role/admin/session/form.html.twig', array(
            'session_form' => $session_form->createView(),
            'quiz_form' => $quiz_form->createView(),
            'request' => $session_request
        ));
    }

    /**
     * @Route("/edit/{id}", name="edit")
     */
    public function sessionEditAction(\Symfony\Component\HttpFoundation\Request $request, Session $session) {
        $data = array(
            'topic' => $session->getTopic(),
            'type' => $session->getType(),
            'studentInstructions' => $session->getStudentInstructions(),
            'mentorInstructions' => $session->getMentorInstructions(),
            'description' => $session->getDescription(),
            // 'uploadedFiles' => $session->getFiles(),
            'sections' => $session->getSections(),
            'graded' => $session->getGraded(),
            'numericGrade' => $session->getNumericGrade(),
            'color' => $session->getColor()
        );

        if ($session instanceof ScheduledSession) {
            $data['repeats'] = $session->getRepeats();
            $data['defaultCapacity'] = $session->getDefaultCapacity();
            $data['defaultDuration'] = $session->getDefaultDuration();
            $data['defaultLocation'] = $session->getDefaultLocation();
        } elseif ($session instanceof Quiz) {
            $data['startDate'] = $session->getStartDate();
            $data['endDate'] = $session->getEndDate();
            $data['room'] = $session->getLocation();
        }

        $session_form = $this->createForm(ScheduledSessionType::class, $data, array(
            'action' => $this->generateUrl('admin_session_edit_scheduled', array(
                'id' => $session->getId()
            ))
        ));

        $quiz_form = $this->createForm(QuizType::class, $data, array(
            'action' => $this->generateUrl('admin_session_edit_quiz', array(
                'id' => $session->getId()
            ))
        ));

        return $this->render('role/admin/session/form.html.twig', array(
            'session_form' => $session_form->createView(),
            'quiz_form' => $quiz_form->createView()
        ));
    }

    /**
     * @Route("/requests/feed/{status}", name="requests_feed")
     */
    public function requestsFeedAction(string $status, Serializer $serializer) {
        $requests = $this->getDoctrine()
            ->getRepository(Request::class)
            ->findByStatus($status);

        if ($status == 'completed' || $status == 'pending') {
            $sessions = $this->getDoctrine()
                ->getRepository(Session::class)
                ->findByRequest(null);

            foreach ($sessions as $session) {
                $r = new Request(
                    $session->getType(),
                    null,
                    $session->getTopic(),
                    $session->getStartDate(),
                    $session->getEndDate(),
                    $session->getStudentInstructions(),
                    $session->getSections()->toArray()
                );

                $r->setSession($session);
                if ($session instanceof Quiz || $session->getRepeats() <= $session->getTimeSlots()->count()) {
                    $r->setStatus('completed');
                } else {
                    $r->setStatus('pending');
                }

                if ($r->getStatus() == $status) {
                    $requests[] = $r;
                }
            }
        }

        $json = $serializer->serialize($requests, array(
            'attributes' => array(
                'id',
                'type' => [
                    'name',
                    'color'
                ],
                'topic',
                'user' => [
                    'firstName',
                    'lastName',
                    'username'
                ],
                'startDate',
                'endDate',
                'status',
                'sections' => [
                    'course' => [
                        'number',
                        'name',
                        'department' => [
                            'name',
                            'abbreviation'
                        ]
                    ],
                    'number',
                    'instructors' => [
                        'firstName',
                        'lastName',
                        'username'
                    ]
                ],
                'session' => [
                    'id',
                    'topic',
                    'type',
                    'repeats',
                    'timeSlots' => ['id']
                ],
                'files' => [
                    'id',
                    'name'
                ],
                'created'
            )
        ));

        return JsonResponse::fromJsonString($json);
    }

    /**
     * @Route("/grades/{id}", name="grades")
     */
    public function gradesAction(Session $session) {
        $type = get_class($session);

        if ($type == Quiz::class) {
            $attendances = $session->getAttendances();

            return $this->render('role/admin/session/grades.html.twig', array(
                'session' => $session,
                'attendances' => $attendances
            ));
        } elseif ($type == ScheduledSession::class) {
            $timeSlots = $session->getTimeSlots();
            $ts = array();
            foreach ($timeSlots as $timeSlot) {
                $ts[$timeSlot->getId()] = array(
                    'timeSlot' => $timeSlot,
                    'students' => array()
                );

                foreach ($session->getStudents() as $student) {
                    $registered = $timeSlot->isRegistered($student);
                    $attended = $timeSlot->hasAttended($student);
                    if ($registered || $attended) {
                        $ts[$timeSlot->getId()]['students'][] = array(
                            'user' => $student,
                            'registered' => $registered,
                            'attendance' => $timeSlot->getAttendance($student)
                        );
                    }
                }
            }

            return $this->render('role/admin/session/grades.html.twig', array(
                'session' => $session,
                'timeSlots' => $ts
            ));
        }
    }

    /**
     * @Route("/register/{id}", name="register")
     */
    public function registerAction(\Symfony\Component\HttpFoundation\Request $request, ScheduledSession $session) {
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
            ))->add('timeSlot', EntityType::class, array(
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
            ))->add('submit', SubmitType::class, array(
                'label' => 'Submit'
            ))->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $registration = new Registration($data['timeSlot'], $data['user']);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($registration);
            $entityManager->flush();

            $this->addFlash('success', 'Success');
            return $this->redirectToRoute('admin_session_grades', array(
                'id' => $session->getId()
            ));
        }

        return $this->render('shared/form/form.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/attend/{id}", name="attend")
     */
    public function attendAction(\Symfony\Component\HttpFoundation\Request $request, Session $session) {
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
        $form->add('timeIn', TimeType::class, array(
            'html5' => true,
            'widget' => 'single_text',
            'mapped' => true,
            'required' => false
        ))->add('timeOut', TimeType::class, array(
            'html5' => true,
            'widget' => 'single_text',
            'mapped' => true,
            'required' => false
        ))->add('submit', SubmitType::class, array(
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
                $attendance->setTimeIn($data['timeIn']);
                $attendance->setTimeOut($data['timeOut']);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($attendance);
            $entityManager->flush();

            $this->addFlash('success', 'Success');
            return $this->redirectToRoute('admin_session_grades', array(
                'id' => $session->getId()
            ));
        }

        return $this->render('shared/form/form.html.twig', array(
            'form' => $form->createView()
        ));
    }
}