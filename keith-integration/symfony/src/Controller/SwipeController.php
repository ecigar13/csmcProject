<?php

namespace App\Controller;

use App\Entity\Course\Course;
use App\Entity\Session\Quiz;
use App\Entity\Session\SessionTimeSlot;
use App\Entity\Session\TimeSlot;
use App\Entity\Session\WalkInActivity;
use App\Entity\User\User;
use App\Form\CardRegisterType;
use App\Form\NoCardType;
use App\Form\SwipeType;
use App\Form\WalkInEntryType;
use App\Form\WalkInExitType;
use App\Utils\SwipeManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;

/**
 * @Route("/swipe", name="swipe_")
 */
class SwipeController extends Controller /* implements ReauthRequiredController */
{
    /**
     * @Route("/walk_in", name="walk_in")
     */
    public function swipeWalkInAction(Request $request) {
        $this->denyAccessUnlessGranted([
            'admin',
            'developer',
            'mentor'
        ]);

        $swipe_form = $this->createForm(SwipeType::class, array(), array(
            'action' => $this->generateUrl('swipe_ajax_walk_in')
        ));

        $register_form = $this->createForm(CardRegisterType::class, array(), array(
            'action' => $this->generateUrl('swipe_ajax_register')
        ));

        $entry_form = $this->createForm(WalkInEntryType::class, array(), array(
            'action' => $this->generateUrl('swipe_ajax_entry')
        ));
        $exit_form = $this->createForm(WalkInExitType::class, array(), array(
            'action' => $this->generateUrl('swipe_ajax_exit')
        ));

        return $this->render('shared/swipe/walk_in.html.twig', array(
            'swipe_form' => $swipe_form->createView(),
            'register_form' => $register_form->createView(),
            'entry_form' => $entry_form->createView(),
            'exit_form' => $exit_form->createView()
        ));
    }

    /**
     * @Route("/ajax/walk_in", name="ajax_walk_in")
     */
    public function swipeAjaxWalkInAction(Request $request, SwipeManager $swipeManager) {
        $form = $this->createForm(SwipeType::class);
        $form->submit($request->request->all());

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $swipe = $form->get('scancode')->getData();

                return $swipeManager->walkInSwipe($swipe);
            }

            return new JsonResponse('malformed_scan', 400);
        }

        return new JsonResponse('invalid', 400);
    }

    /**
     * @Route("/ajax/entry", name="ajax_entry")
     */
    public function swipeAjaxWalkInEntryAction(Request $request, SwipeManager $swipeManager) {
        $form = $this->createForm(WalkInEntryType::class);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $user = $this->getDoctrine()
                ->getRepository(User::class)
                ->find($data['user']);

            $activity = $this->getDoctrine()
                ->getRepository(WalkInActivity::class)
                ->find($data['activity']);

            $course = $this->getDoctrine()
                ->getRepository(Course::class)
                ->find($data['course']);

            $quiz_activity = $this->getDoctrine()
                ->getRepository(WalkInActivity::class)
                ->findByName('Take a Quiz');
            if ($activity->getName() == 'Take a Quiz') {
                $quiz = $this->getDoctrine()
                    ->getRepository(Quiz::class)
                    ->find($data['quiz']);

            } else {
                $quiz = null;
            }

            return $swipeManager->entry($user, $data['topic'], $activity, $course, $quiz);
        }

        return new JsonResponse('invalid', 400);
    }

    /**
     * @Route("/ajax/exit", name="ajax_exit")
     */
    public function swipeAjaxWalkInExitAction(Request $request, SwipeManager $swipeManager) {
        $form = $this->createForm(WalkInExitType::class);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $user = $this->getDoctrine()
                ->getRepository(User::class)
                ->find($data['user']);

            $mentors = array();
            foreach ($data['mentors'] as $mentor) {
                $mentors[] = $this->getDoctrine()
                    ->getRepository(User::class)
                    ->find($mentor);
            }

            return $swipeManager->exit($user, $mentors, $data['feedback'] ?: null);
        }

        return new JsonResponse('invalid', 400);
    }


    /**
     * @Route("/session/{id}", name="session")
     */
    public function swipeSessionAction(Request $request, SessionTimeSlot $timeSlot) {
        // $session->set('reauth', true);
        // $this->denyAccessUnlessGranted([
        //     'admin',
        //     'mentor',
        // ]);

        $swipe_form = $this->createForm(SwipeType::class, array(
            'session' => $timeSlot->getId()
        ), array(
            'action' => $this->generateUrl('swipe_ajax_session')
        ));

        $no_card_form = $this->createForm(NoCardType::class, array(
            'session' => $timeSlot->getId()
        ), array(
            'action' => $this->generateUrl('swipe_ajax_session', array('no-card' => true))
        ));

        $register_form = $this->createForm(CardRegisterType::class, array(
            'session' => $timeSlot->getId()
        ), array(
            'action' => $this->generateUrl('swipe_ajax_register')
        ));

        return $this->render('shared/swipe/session.html.twig', array(
            'session' => $timeSlot,
            'swipe_form' => $swipe_form->createView(),
            'no_card_form' => $no_card_form->createView(),
            'register_form' => $register_form->createView()
        ));
    }

    /**
     * @Route("/ajax/session", name="ajax_session")
     */
    public function swipeAjaxSessionAction(Request $request, SwipeManager $swipeManager) {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException();
        }

        if ($request->query->get('no-card')) {
            $form = $this->createForm(NoCardType::class);
            $form->submit($request->request->all());
            $no_card = true;
        } else {
            $form = $this->createForm(SwipeType::class);
            $form->submit($request->request->all());
            $no_card = false;
        }

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $session_id = $form->get('session')->getData();

                $session = $em->getRepository(SessionTimeSlot::class)
                    ->find($session_id);

                if ($no_card) {
                    $data = $form->getData();
                    $username = $data['username'];
                    $password = $data['password'];

                    return $swipeManager->sessionLogIn($session, $username, $password);
                } else {
                    $scancode = $form->get('scancode')->getData();

                    return $swipeManager->sessionSwipe($session, $scancode);
                }
            } else {
                return new JsonResponse($no_card ? 'bad_credentials' : 'malformed_scan', 400);
            }
        }

        return new JsonResponse('invalid', 400);
    }

    /**
     * @Route("/ajax/register", name="ajax_register")
     */
    public function swipeAjaxRegisterAction(Request $request, SwipeManager $swipeManager) {
        if (!$request->isXmlHttpRequest()) {
            throw new MethodNotAllowedException();
        }

        $form = $this->createForm(CardRegisterType::class);
        $form->submit($request->request->all());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $username = $data['username'];
            $password = $data['password'];
            $swipe = $data['swipe'];
            $session_id = $data['session'];

            $user = $swipeManager->register($username, $password, $swipe, true);

            if ($user === false) {
                return new JsonResponse('bad_credentials', 400);
            } elseif ($user === null) {
                return new JsonResponse('no_user', 404);
            }

            if ($session_id) {
                $session = $this->getDoctrine()
                    ->getRepository(SessionTimeSlot::class)
                    ->find($session_id);
                return $swipeManager->sessionSwipe($session, $swipe);
            } else {
                return $swipeManager->walkInSwipe($swipe);
            }
        }

        return new JsonResponse('invalid', 400);
    }
}