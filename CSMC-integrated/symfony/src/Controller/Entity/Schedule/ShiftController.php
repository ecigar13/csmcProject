<?php

namespace App\Controller\Entity\Schedule;


use App\Entity\Schedule\Absence;
use App\Entity\Schedule\ScheduledShift;
use App\Entity\Schedule\Shift;
use App\Entity\Schedule\ShiftAssignment;
use App\Entity\Schedule\Subject;
use App\Entity\Session\Session;
use App\Entity\User\User;
use App\Form\Schedule\ShiftSubjectType;
use App\Form\Schedule\ShiftTimeType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Entity\Schedule\ShiftTime;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ShiftController extends Controller {

    /**
     * @Route("/schedule/shift/create/subject", name="shift_create_subject")
     */
    public function shiftCreateSubjectAction(Request $request) {
        $this->denyAccessUnlessGranted(['admin', 'schedule', ]);

        $subject = new Subject();
        $form = $this->createForm(ShiftSubjectType::class, $subject);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $subject = $form->getData();
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($subject);
            $em->flush();

            $this->addFlash('notice', 'Successfully created shift subject "' . $subject->getName() . '"');
            unset($subject);
            unset($form);
            $subject = new Subject();
            $form = $this->createForm(ShiftSubjectType::class, $subject);
        }

        return $this->render(
            'shared/form/form.html.twig',
            array(
                'user' => $this->getUser(),
                'form' => $form->createView()
            )
        );
    }

    /**
     * @Route("/schedule/shift/create/time", name="shift_create_time")
     */
    public function shiftCreateTimeAction(Request $request) {
        $this->denyAccessUnlessGranted(['admin', 'schedule', ]);

        $time = new ShiftTime();
        $form = $this->createForm(ShiftTimeType::class, $time);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $time = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($time);
            $em->flush();

            $this->addFlash('notice', 'Successfully created shift time "' . $time . '"');
            unset($time);
            unset($form);
            $time = new ShiftTime();
            $form = $this->createForm(ShiftTimeType::class, $time);
        }

        return $this->render(
            'shared/form/form.html.twig',
            array(
                'user' => $this->getUser(),
                'form' => $form->createView()
            )
        );
    }

    /**
     * @Route("/schedule/shift/edit/{id}", name="shift_edit")
     */
    public function shiftEditAction(Request $request, $id) {
        $this->denyAccessUnlessGranted(['admin', 'schedule', ]);

        $shift = $this->getDoctrine()->getRepository('App\Entity\Schedule\Shift')->find($id);

        if (!$shift) {
            throw $this->createNotFoundException('No shift found with id ' . $id);
        } else {
            $form = $this->createForm(ShiftType::class, $shift);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $time = $form->getData();
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->addFlash('notice', 'Shift successfully updated!');
                return $this->redirectToRoute('schedule');
            }

            return $this->render(
                'shared/form/form.html.twig',
                array(
                    'user' => $this->getUser(),
                    'form' => $form->createView()
                )
            );
        }
    }

    /**
     * @Route("/ajax/schedule/shift/assignment/date", name="shift_assignment_date_ajax")
     */
    public function shiftAssignmentDateAjax(Request $request) {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedHttpException();
        }

        $normalizer = new ObjectNormalizer();
        $normalizer->setIgnoredAttributes(array('shift'));
        $callback = function ($dateTime) {
            return $dateTime instanceof \DateTime
                ? $dateTime->format('g:i A')
                : '';
        };
        $normalizer->setCallbacks(
            array(
                'startTime' => $callback,
                'endTime' => $callback
            )
        );
        $encoder = new JsonEncoder();
        $serializer = new Serializer(array($normalizer), array($encoder));

        if ($request->request->has('date')) {
            $date = $request->request->get('date');
            $date = new \DateTime($date);
            // get times by date
            $scheduled_shifts = $this->getDoctrine()
                ->getRepository('App\Entity\Schedule\ScheduledShift')
                ->findByDate($date);
            $times = array();
            foreach ($scheduled_shifts as $shift) {
                $times[] = $shift->getShift()->getTime();
            }

            $times = array_unique($times);
            $times = $serializer->serialize($times, 'json');
            $response = new JsonResponse();
            $response->setData($times);
            return $response;
        } else {
            return new Response('', 400);
        }
    }

    /**
     * @Route("/ajax/schedule/shift/assignment/time", name="shift_assignment_time_ajax")
     */
    public function shiftAssignmentTimeAjax(Request $request) {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedHttpException();
        }

        $normalizer = new ObjectNormalizer();
        $normalizer->setIgnoredAttributes(array('scheduledShift'));
        $mentor_callback = function ($mentor) {
            return $mentor instanceof User
                ? array(
                    'id' => $mentor->getId(),
                    'name' => $mentor->__toString()
                )
                : '';
        };
        $session_callback = function ($session) {
            return $session instanceof Session
                ? $session->getTopic()
                : '';
        };
        $absence_callback = function ($absence) {
            return $absence instanceof Absence
                ? true
                : false;
        };
        $normalizer->setCallbacks(
            array(
                'mentor' => $mentor_callback,
                'session' => $session_callback,
                'absence' => $absence_callback
            )
        );
        $encoder = new JsonEncoder();
        $serializer = new Serializer(array($normalizer), array($encoder));

        if ($request->request->has('date') && $request->request->has('time')) {
            $date = $request->request->get('date');
            $time = $request->request->get('time');

            $scheduled_shifts = $this->getDoctrine()
                ->getRepository('App\Entity\Schedule\ScheduledShift')
                ->findByDateAndTime($date, $time);

            $mentors = array();
            foreach ($scheduled_shifts as $shift) {
                $m = $shift->getMentors();
                $mentors = array_merge($mentors, $m->toArray());
            }

            $mentors = $serializer->serialize($mentors, 'json');
            $response = new JsonResponse();
            $response->setData($mentors);
            return $response;
        } else {
            return new Response('', 400);
        }
    }

    /**
     * @Route("/schedule/shift/time/feed", name="shift_time_feed")
     */
    public function shiftTimeFeed(Request $request, \App\Utils\Serializer $serializer) {
        // if (!$request->isXmlHttpRequest()) {
        //     throw new MethodNotAllowedHttpException([]);
        // }

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($request->query->get('user'));
        $date = new \DateTime($request->query->get('date'));

        $shifts = $this->getDoctrine()
            ->getRepository(ShiftAssignment::class)
            ->findByUserAndDate($user, $date);

        $callback = function ($shift) {
            return $shift instanceof ScheduledShift
                ? $shift->getShift()->getStartTime()->format('g:i A')
                : '';
        };

        $data = $serializer->serialize($shifts, array(
            'attributes' => [
                'id',
                'scheduledShift'
            ],
            'callbacks' => [
                'scheduledShift' => $callback
            ]
        ));

        return JsonResponse::fromJsonString($data);
    }
}