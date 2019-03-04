<?php

namespace App\Controller;

use App\Entity\Course\Section;
use App\Entity\Event\Event;
use App\Entity\Misc\Room;
use App\Entity\Session\QuizTimeSlot;
use App\Entity\Session\ScheduledSession;
use App\Entity\Session\Session;
use App\Entity\Session\SessionTimeSlot;
use App\Utils\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends Controller {
    /**
     * @Route("/events", name="events")
     */
    public function eventFeedAction(Request $request, Serializer $serializer) {
        $start = new \DateTime($request->request->get('start'));
        $end = new \DateTime($request->request->get('end'));

        $events = $this->getDoctrine()
            ->getRepository(Event::class)
            ->findBetween($start, $end);

        $room_callback = function ($location) {
            return $location instanceof Room
                ? [
                    'id' => $location->getId(),
                    'room' => $location->__toString()
                ]
                : '';
        };

        $session_attributes = [
            'id',
            'files' => ['id'],
            'topic',
            'description',
            'studentInstructions',
            'mentorInstructions',
            'sections' => [
                'id',
                'number',
                'course' => [
                    'id',
                    'department' => [
                        'id',
                        'name',
                        'abbreviation'
                    ],
                    'name',
                    'number'
                ],
                'instructors' => [
                    'id',
                    'firstName',
                    'lastName',
                    'username'
                ]
            ],
            'type',
            'graded',
            'numericGrade',
            'color',
            'repeats',
        ];

        $json = $serializer->serialize($events, array(
            'attributes' => array(
                'id',
                'startTime',
                'endTime',
                'location',
                'color',
                'capacity',
                'registrations' => [
                    'id',
                    'user' => [
                        'id',
                        'firstName',
                        'lastName',
                        'username'
                    ],
                    'time'
                ],
                'session' => $session_attributes,
                'quiz' => $session_attributes,
                'assignments' => [
                    'id',
                    'absence' => [
                        'id',
                        'reason'
                    ],
                    'mentor' => [
                        'id',
                        'firstName',
                        'lastName',
                        'username',
                        'profile' => [
                            'preferredName'
                        ]
                    ]
                ]
            ),
            'callbacks' => array(
                'location' => $room_callback,
            )
        ));

        $response = JsonResponse::fromJsonString($json);

        return $response;
    }
}