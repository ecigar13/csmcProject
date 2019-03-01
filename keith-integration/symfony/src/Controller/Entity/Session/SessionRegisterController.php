<?php

namespace App\Controller\Entity\Session;

use App\Entity\Session\Registration;
use App\Entity\Session\ScheduledSession;
use App\Entity\Session\Session;
use App\Entity\Session\SessionTimeSlot;
use App\Entity\Session\TimeSlot;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class SessionRegisterController extends Controller {
    /**
     * @Route("/session/register/{sid}", name="session_register_timeslot")
     */
    public function sessionRegisterTimeslotAction($sid) {
        $session = $this->getDoctrine()->getRepository(ScheduledSession::class)->find($sid);

        if (!$session) {
            throw $this->createNotFoundException('No session with id ' . $sid);
        } elseif ($session->getEndDate() < (new \DateTime())->setTime(0, 0, 0)) {
            throw $this->createAccessDeniedException('Registration Time Expired for session with id ' . $sid);
        } else {
            $timeslots = $this->getDoctrine()
                ->getRepository(SessionTimeSlot::class)
                ->findBySession($session, array(
                    'startTime' => 'ASC'
                ));

            return $this->render('role/student/session/register/timeslots.html.twig', array(
                'session' => $session,
                'timeslots' => $timeslots
            ));
        }
    }

    /**
     * @Route("/session/register/timeslot/{tid}", name="session_register_form")
     */
    public function sessionRegisterFormAction(Request $request, $tid) {
        $timeslot = $this->getDoctrine()->getRepository(SessionTimeSlot::class)->find($tid);

        if (!$timeslot) {
            throw $this->createNotFoundException('No time slot with id ' . $tid);
        } elseif ($timeslot->getStartTime() < new \DateTime()) {
            throw $this->createAccessDeniedException('Registration Time Expired for time slot with id ' . $tid); // TODO make a better solution
        } else {
            $session = $timeslot->getSession();
            //check if registered
            $registration = $this->getDoctrine()
                ->getRepository(Registration::class)
                ->findOneBy(array(
                    'timeSlot' => $timeslot,
                    'user' => $this->getUser()
                ));
            $form = $this->createFormBuilder();
            if ($registration) {
                $registered = true;
                $string = 'Unregister';
                $full = false;
            } else {
                // TODO put this back, maybe its enough to have them unable to click button for nowo
                // if($session->isRegistered($this->getUser() || $session->hasAttended($this->getUser()))) {
                //     throw $this->createAccessDeniedException('You are already registered for a timeslot in this session'); // TODO make better solution
                // }

                $registered = false;
                if ($timeslot->getRemainingSeats() <= 0) {
                    $string = 'Session Full';
                    $full = true;
                } else {
                    $string = 'Register';
                    $form->add('policyCheck', CheckboxType::class, array(
                        'required' => true,
                        'label' => false
                    ));
                    $full = false;
                }
            }

            $form = $form->add('submit', SubmitType::class, array(
                'label' => $string,
                'disabled' => $full
            ))
                ->getForm();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                if ($registered) {
                    $em->remove($registration);
                    //$this->get('app.mailer')->sendSessionUnregisteredEmail($this->getUser(), $timeslot);
                } else {
                    $registration = new Registration($timeslot, $this->getUser());
                    $em->persist($registration);
                    // $this->get('app.mailer')->sendSessionRegistrationMail($this->getUser(), $timeslot);
                }
                $em->flush();

                $this->addFlash('notice', 'Successfully registered for session "' . $session->getTopic() . '"');
                return $this->redirectToRoute('session_schedule');
            }

            $policy = $this->getDoctrine()
                ->getRepository('App\Entity\Misc\Policy')
                ->findByName('Students');

            return $this->render('role/student/session/register/register.html.twig', array(
                'session' => $session,
                'timeslot' => $timeslot,
                'policy' => $policy,
                'form' => $form->createView()
            ));
        }
    }
}