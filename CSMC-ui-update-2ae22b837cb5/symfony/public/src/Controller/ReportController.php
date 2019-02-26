<?php

namespace App\Controller;

use App\Entity\Course\Course;
use App\Entity\Course\Section;
use App\Entity\Session\Session;
use App\Entity\Session\WalkInActivity;
use App\Entity\Session\WalkInAttendance;
use App\Entity\User\User;

// use App\Form\Report\CourseReportType;
// use App\Form\Report\SessionReportType;
// use App\Form\Report\TimeSheetReportType;
// use App\Form\Report\WalkInReportType;
use App\Utils\ReportManager;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ReportController extends Controller {
    /**
     * @Route("/report/generator", name="report_generator")
     */
    public function reportGeneratorAction(ReportManager $reportManager) {
        $this->denyAccessUnlessGranted(['developer']);

        return $this->render('shared/report/report.html.twig');
    }

    /**
     * @Route("/report", name="report")
     */
    public function reportAction(Request $request) {
        $this->denyAccessUnlessGranted([
            'instructor'
        ]);

        $user = $this->getUser();

        $form = $this->createFormBuilder()
            ->add('course', EntityType::class, array(
                'class' => Section::class,
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('s')
                        ->join('s.instructors', 'i')
                        ->where('i = :user')
                        ->setParameter('user', $user);
                    return $qb;
                },
                'placeholder' => '',
                'required' => true
            ))->add('session', EntityType::class, array(
                'class' => Session::class,
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('s')
                        ->join('s.sections', 'ss')
                        ->join('ss.instructors', 'i')
                        ->where('i = :user')
                        ->setParameter('user', $user);
                    return $qb;
                },
                'placeholder' => 'Walk Ins',
                'choice_label' => 'topic',
                'required' => false
            ))->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        $report = null;
        $report_type = null;
        if ($form->isSubmitted() && $form->isValid()) {
            // do report stuff
            $data = $form->getData();

            $report = $this->getReport($data['course'], $data['session']);

            $report_type = $data['session'] != null ? 'session' : 'walkins';
        }

        return $this->render('role/instructor/report/report_home.html.twig', array(
            'form' => $form->createView(),
            'report' => $report,
            'report_type' => $report_type
        ));
    }

    private function getReport(Section $section, Session $session = null) {
        $em = $this->getDoctrine()
            ->getManager();

        $report = array();
        if ($session == null) {
            // walk ins
            if ($section == null) {
                // TODO
                // all sections for the user
                $sections = $em->getRepository(Section::class)
                    ->createQueryBuilder('s')
                    ->join('s.instructors', 'i')
                    ->where('i = :user')
                    ->setParameter('user', $this->getUser())
                    ->getQuery()
                    ->execute();
            } else {
                $course = $section->getCourse();

                $qb = $em->getRepository(WalkInAttendance::class)
                    ->createQueryBuilder('w');
                $qb->join('w.course', 'c')
                    ->where('c = :course')
                    ->setParameter('course', $course);

                $potential_students = $qb->getQuery()->execute();

                foreach ($potential_students as $potential_student) {
                    foreach ($section->getRoster() as $student) {
                        if ($student->getId() == $potential_student->getUser()->getId()) {
                            $report[] = $potential_student;
                            break;
                        }
                    }
                }
            }
        } else {
            // sessions
            if ($section == null) {
                // TODO
                // all sections for the user
            } else {
                foreach($section->getRoster() as $student) {
                    $report[$student->getId()] = array(
                        'attendance' => $session->getAttendance($student),
                        'user' => $student
                    );
                }
            }
        }

        return $report;
    }
    //
    // /**
    //  * @Route("/report/course", name="report_course")
    //  */
    // public function reportCourseAction(Request $request) {
    //     $this->denyAccessUnlessGranted([
    //         'admin',
    //         'report'
    //     ]);
    //
    //     $form = $this->createForm(CourseReportType::class);
    //
    //     return $this->render('report/report.html.twig', array(
    //         'form' => $form->createView()
    //     ));
    // }
    //
    // /**
    //  * @Route("/ajax/report/course", name="report_course_ajax")
    //  */
    // public function reportCourseAjaxAction(Request $request) {
    //     if (!$request->isXmlHttpRequest()) {
    //         throw new MethodNotAllowedException();
    //     }
    //
    //     $this->denyAccessUnlessGranted([
    //         'admin',
    //         'report'
    //     ]);
    //
    //     $data = array();
    //     $form = $this->createForm(CourseReportType::class, $data);
    //
    //     $form->handleRequest($request);
    //
    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $data = $form->getData();
    //
    //         $reporter = $this->get('app.reporter');
    //
    //         $data = $reporter->getCourseReport($data['course']);
    //     }
    //
    //     return new JsonResponse(array(
    //         'data' => $data
    //     ));
    // }
    //
    // /**
    //  * @Route("/report/section", name="report_section")
    //  */
    // public function reportSectionAction() {
    //     $this->denyAccessUnlessGranted([
    //         'admin',
    //         'report'
    //     ]);
    //
    //     $form = $this->createForm(SectionReportType::class);
    //
    //     return $this->render('report/report.html.twig', array(
    //         'form' => $form->createView()
    //     ));
    // }
    //
    // /**
    //  * @Route("/ajax/report/section", name="report_section_ajax")
    //  */
    // public function reportSectionAjaxAction(Request $request) {
    //     if (!$request->isXmlHttpRequest()) {
    //         throw new MethodNotAllowedException();
    //     }
    //
    //     $this->denyAccessUnlessGranted([
    //         'admin',
    //         'report'
    //     ]);
    //
    //     $data = array();
    //     $form = $this->createForm(SectionReportType::class, $data);
    //
    //     $form->handleRequest($request);
    //
    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $data = $form->getData();
    //
    //         $reporter = $this->get('app.reporter');
    //
    //         $data = $reporter->getCourseReport($data['course']);
    //     }
    //
    //     return new JsonResponse(array(
    //         'data' => $data
    //     ));
    // }
    //
    // /**
    //  * @Route("/report/session", name="report_session")
    //  */
    // public function reportSessionAction() {
    //     $this->denyAccessUnlessGranted([
    //         'admin',
    //         'report'
    //     ]);
    //
    //     $form = $this->createForm(SessionReportType::class);
    //
    //     return $this->render('report/report.html.twig', array(
    //         'form' => $form->createView()
    //     ));
    // }
    //
    // /**
    //  * @Route("/ajax/report/session", name="report_session_ajax")
    //  */
    // public function reportSessionAjaxAction(Request $request) {
    //     if (!$request->isXmlHttpRequest()) {
    //         throw new MethodNotAllowedException();
    //     }
    //
    //     $this->denyAccessUnlessGranted([
    //         'admin',
    //         'report'
    //     ]);
    //
    //     $data = array();
    //     $form = $this->createForm(SessionReportType::class, $data);
    //
    //     $form->handleRequest($request);
    //
    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $data = $form->getData();
    //
    //         $reporter = $this->get('app.reporter');
    //
    //         $data = $reporter->getCourseReport($data['course']);
    //     }
    //
    //     return new JsonResponse(array(
    //         'data' => $data
    //     ));
    // }
    //
    // // TODO remove or use all above actions
    //
    // /**
    //  * @Route("/report/walkin", name="report_walkin")
    //  */
    // public function reportWalkInAction(Request $request) {
    //     $this->denyAccessUnlessGranted([
    //         'admin',
    //         'report'
    //     ]);
    //
    //     $data = array();
    //     $form = $this->createForm(WalkInReportType::class, $data);
    //
    //     $reporter = $this->get('app.reporter');
    //
    //     $form->handleRequest($request);
    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $data = $form->getData();
    //         $report = $reporter->getWalkInReport($data['course']);
    //     } else {
    //         $report = $reporter->getWalkInReport();
    //     }
    //
    //     return $this->render('report/walk_in.html.twig', array(
    //         'form' => $form->createView(),
    //         'report' => $report
    //     ));
    // }

    /**
     * @Route("/report/walkin/{id}/download", name="report_walkin_download")
     */
    public function reportWalkInDownloadAction(Request $request, $id) {
        $this->denyAccessUnlessGranted([
            'admin',
            'report',
            'instructor',
            'teaching_assistant'
        ]);

        if ($this->isGranted('instructor')) {
            $walkins = $this->walkins = $this->getDoctrine()
                ->getRepository(WalkInAttendance::class)
                ->findBySection($this->getDoctrine()
                    ->getRepository(Section::class)
                    ->find($id));
        } elseif ($id == 'all') {
            $walkins = $this->getDoctrine()
                ->getRepository(WalkInAttendance::class)
                ->findAll();
        } else {
            $course = $this->getDoctrine()
                ->getRepository(Course::class)
                ->find($id);
            if (!$course) {
                throw $this->createNotFoundException();
            }

            $walkins = $this->getDoctrine()
                ->getRepository(WalkInAttendance::class)
                ->findByCourse($course);
        }


        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizer = new ObjectNormalizer($classMetadataFactory);
        $time_callback = function ($dateTime) {
            return $dateTime instanceof \DateTime
                ? $dateTime->format('m/d/Y g:i A')
                : '';
        };
        $activity_callback = function ($activity) {
            return $activity instanceof WalkInActivity
                ? $activity->getName()
                : '';
        };
        $course_callback = function ($course) {
            return $course instanceof Course
                ? ($course->getDepartment()->getAbbreviation() . ' ' . $course->getNumber())
                : '';
        };
        $section_callback = function ($section) {
            return $section instanceof Section
                ? $section->getNumber()
                : '';
        };
        $normalizer->setCallbacks(array(
            'user' => function ($user) {
                return $user instanceof User
                    ? $user->getLastName() . ':' . $user->getFirstName() . ':' . $user->getUsername()
                    : '';
            },
            // 'lName' => function ($user) {
            //     return $user instanceof User
            //         ? $user->getLastName()
            //         : '';
            // },
            // 'fName' => function ($user) {
            //     return $user instanceof User
            //         ? $user->getFirstName()
            //         : '';
            // },
            // 'uName' => function ($user) {
            //     return $user instanceof User
            //         ? $user->getUsername()
            //         : '';
            // },
            'timeIn' => $time_callback,
            'timeOut' => $time_callback,
            'activity' => $activity_callback,
            'course' => $course_callback,
            'section' => $section_callback
        ));
        $encoder = new CsvEncoder();
        $serializer = new Serializer(array($normalizer), array($encoder));
        $report = $serializer->normalize($walkins, null, array('groups' => array('walk_in_report')));

        for ($i = 0; $i < count($report); $i++) {
            $report[$i] = array(
                'Last Name' => explode(':', $report[$i]['user'])[0],
                'First Name' => explode(':', $report[$i]['user'])[1],
                'Username' => explode(':', $report[$i]['user'])[2],
                'Time In' => $report[$i]['timeIn'],
                'Time Out' => $report[$i]['timeOut'],
                'Activity' => $report[$i]['activity'],
                'Course' => $report[$i]['course'],
                'Section' => $report[$i]['section']
            );
        }


        $data = $serializer->serialize($report, 'csv');

        $response = new Response();
        if ($this->isGranted('instructor')) {
            $section = $this->getDoctrine()->getRepository('App\Course:Section')->find($id);
            $filename = $section->getCourse()->getDepartment()->getAbbreviation() . $section->getCourse()->getNumber()
                        . '_' . 'walk_ins.csv';
        } else {

            $filename = ($id == 'all'
                    ? 'all'
                    : $course->getDepartment()->getAbbreviation() . $section->getCourse()->getNumber())
                        . '_' . 'walk_ins.csv';
        }
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->headers->set('Content-disposition', $disposition);
        $response->headers->set('Content-type', 'text/csv');
        $response->setContent($data);
        return $response;
    }

    // /**
    //  * @Route("/report/timesheet", name="report_timesheet")
    //  */
    // public function reportTimeSheetAjaxAction(Request $request) {
    //     $this->denyAccessUnlessGranted([
    //         'admin',
    //         'report'
    //     ]);
    //
    //     $data = array();
    //     $form = $this->createForm(TimeSheetReportType::class, $data);
    //
    //     $reporter = $this->get('app.reporter');
    //
    //     $form->handleRequest($request);
    //
    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $data = $form->getData();
    //         $date = date('m/d/Y', strtotime('last Sunday', $data['date']->getTimestamp()));;
    //         $report = $reporter->getTimeSheetReport($date, $data['mentor']);
    //     } else {
    //         $date = date('m/d/Y', strtotime('last Sunday', (new \DateTime())->getTimestamp()));;
    //         $report = $reporter->getTimeSheetReport($date);
    //         // TODO add way to see users without times
    //         $this->addFlash('warning', 'Users with no times for the week will not show. This will be changed sometime in the future');
    //     }
    //
    //     $period = new \DatePeriod(new \DateTime($date), new \DateInterval('P1D'), (new \DateTime($date))->modify('+8 days'));
    //
    //     return $this->render('report/time_sheet.html.twig', array(
    //         'form' => $form->createView(),
    //         'period' => $period,
    //         'report' => $report
    //     ));
    // }

    /**
     * @Route("/report/section/{id}/grades/download", name="report_section_grades_download")
     */
    public function reportSectionGradesDownloadAction(Request $request, $id) {
        $this->denyAccessUnlessGranted([
            'admin',
            'report',
            'instructor',
            'teaching_assistant'
        ]);

        $section = $this->getDoctrine()
            ->getRepository('App\Entity\Course\Section')
            ->find($id);

        $roster = $section->getStudents()->matching(Criteria::create()->orderBy(array(
            'lastName' => Criteria::DESC,
            'firstName' => Criteria::DESC
        )));

        $scheduled_sessions = $this->getDoctrine()
            ->getRepository('App\Entity\Session\ScheduledSession')
            ->findBySection($section);

        $quizzes = $this->getDoctrine()
            ->getRepository('App\Entity\Session\Quiz')
            ->findBySection($section);

        $sessions = array_merge($scheduled_sessions ?? array(), $quizzes ?? array());

        $report = array();
        foreach ($roster as $student) {
            foreach ($sessions as $session) {
                $attendances = $session->getAttendances();
                $attendance = $attendances->matching(Criteria::create()->where(Criteria::expr()->eq('user', $student)));
                if (!$attendance->isEmpty()) {
                    $attendance = $attendance->first();
                    if ($session->isGraded()) {
                        $student_grades[$session->getTopic()] = $attendance->getGrade();
                    } else {
                        $student_grades[$session->getTopic()] = 1; // TODO: consider making 'attended' or something equiv
                    }
                } else {
                    $student_grades[$session->getTopic()] = 'N/A'; // TODO: how to handle ungraded sessions
                }
            }
            $report[] = array_merge(array(
                'Last Name' => $student->getLastName(),
                'First Name' => $student->getFirstName(),
                'Username' => $student->getUsername()
            ), $student_grades);
        }

        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizer = new ObjectNormalizer($classMetadataFactory);
        $encoder = new CsvEncoder();
        $serializer = new Serializer(array($normalizer), array($encoder));
        $report = $serializer->normalize($report);
        $data = $serializer->serialize($report, 'csv');

        $response = new Response();
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $section->getCourse()->getDepartment()->getAbbreviation() . $section->getCourse()->getNumber() . '_' .
            $section->getNumber() . '_' . 'grades.csv'
        );
        $response->headers->set('Content-disposition', $disposition);
        $response->headers->set('Content-type', 'text/csv');
        $response->setContent($data);
        return $response;
    }

    /**
     * @Route("/report/semester", name="report_semester")
     */
    public function reportSemester(Request $request) {
        $this->denyAccessUnlessGranted([
            'admin',
            'report'
        ]);

        // TODO add support for semesters
        // // faculty, room, course, duration, hourly, hourly by day, weekly
        // $data = array();
        // $form = $this->createForm(WalkInReportType::class, $data);
        //
        // $form->handleRequest($request);

        // if ($form->isSubmitted() && $form->isValid()) {
        //      $data = $form->getData();

        $reporter = $this->get('app.reporter');

        $report = $reporter->getSemesterReport();
        // } else {
        //     $report = null;
        // }

        return $this->render('report/semester.html.twig', array(
            //    'form' => $form->createView(),
            'report' => $report
        ));
    }
}