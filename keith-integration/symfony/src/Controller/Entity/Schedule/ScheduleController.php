<?php

namespace App\Controller\Entity\Schedule;

use App\Entity\Misc\Room;
use App\Entity\Misc\Semester;
use App\Entity\Schedule\Shift;
use App\Entity\Schedule\ShiftAssignment;
use App\Entity\Schedule\Subject;
use App\Entity\Schedule\ShiftTime;
use App\Entity\Schedule\ScheduledShift;
use App\Entity\Session\Quiz;
use App\Entity\Session\TimeSlot;
use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Schedule\Schedule;
use Symfony\Component\Validator\Constraints\DateTime;

class ScheduleController extends Controller {
    /**
     * @Route("/schedule", name="schedule")
     */
    public function scheduleAction() {
        return $this->redirectToRoute('schedule_weekly');
    }

    /**
     * @Route("/schedule/weekly/{date}", name="schedule_weekly", requirements={"date": "\d{4}-\d{2}-\d{2}"})
     */
    public function scheduleWeeklyAction($date = null) {
        $this->denyAccessUnlessGranted([
            'admin',
            'schedule',
            'mentor'
        ]);

        $semester = $this->getDoctrine()
            ->getRepository(Semester::class)
            ->findActive();

        if ($semester == null) {
            throw $this->createNotFoundException();
        }

        $schedule = $semester->getSchedule();

        $rooms = $this->getDoctrine()
            ->getRepository(Room::class)
            ->findAll();

        if (!$schedule) {
            throw $this->createNotFoundException('No schedule found!');
        } else {

            // TODO: this is stupid, change to not use an exception
            try {
                $date_start = new \DateTime($date);
                $date_end = (new \DateTime($date))->add(new \DateInterval('P7D'));
            } catch (\Exception $e) {
                $date_start = new \DateTime();
                $date_end = (new \DateTime())->add(new \DateInterval('P7D'));
            }

            $date_period = new \DatePeriod($date_start, new \DateInterval('P1D'), $date_end);

            $shifts = array();
            $sessions = array();
            $quizzes = array();
            foreach ($date_period as $day) {
                $day_num = $day->format('w');
                $shifts[$day_num] = array(
                    'shifts' => array(),
                    'times' => array()
                );

                $criteria = Criteria::create()
                    ->where(Criteria::expr()->eq('date', $day));

                $scheduled_shifts_by_day = $schedule->getScheduledShifts()->matching($criteria);

                foreach ($scheduled_shifts_by_day as $shift) {
                    $assignments = array();
                    foreach($shift->getShift()->getSubjects() as $shift_subject) {
                        $assignments[$shift_subject->getSubject()->getId()] = array(
                            'subject' => $shift_subject->getSubject(),
                            'assignments' => array()
                        );
                    }

                    $assignments['shift_leader'] = array(
                        'subject' => 'shift_leader',
                        'assignments' => array()
                    );

                    foreach ($shift->getAssignments() as $assignment) {
                        if($assignment->getSubject() != null) {
                            $assignments[$assignment->getSubject()->getId()]['assignments'][] = $assignment;
                        } else {
                            $assignments['shift_leader']['assignments'][] = $assignment;
                        }
                    }

                    $shifts[$day_num]['shifts'][strval($shift->getShift()->getRoom())][$shift->getShift()
                        ->getStartTime()
                        ->format('U')]['subjects'] = $assignments;
                }

                $shifts_by_day = $this->getDoctrine()
                    ->getRepository(Shift::class)
                    ->findByDay($day_num);

                foreach ($shifts_by_day as $shift) {
                    $shifts[$day_num]['times'][$shift->getStartTime()->format('U')] = [
                        'start' => $shift->getStartTime(),
                        'end' => $shift->getEndTime()
                    ];
                }

                ksort($shifts[$day_num]['times']);

                $sessions[$day_num] = $this->getDoctrine()
                    ->getRepository(TimeSlot::class)
                    ->findByDay($day);

                $quizzes[$day_num] = $this->getDoctrine()
                    ->getRepository(Quiz::class)
                    ->findByDay($day);
            }

            return $this->render('role/mentor/schedule/weekly.html.twig', array(
                'shifts' => $shifts,
                'sessions' => $sessions,
                'quizzes' => $quizzes,
                'rooms' => $rooms,
                'date_period' => $date_period
            ));
        }
    }

    /**
     * @Route("/schedule/upload", name="schedule_upload")
     */
    public function scheduleCreateAction(Request $request) {
        $this->denyAccessUnlessGranted([
            'admin',
            'schedule'
        ]);

        $form = $this->createFormBuilder()
            ->add('start', DateType::class, array(
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd'
            ))
            ->add('end', DateType::class, array(
                'html5' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
            ))
            ->add('file', FileType::class)
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            //TODO replace old school file upload way
            if ($file = fopen($data['file'], 'r')) {
                $csv = array_map('str_getcsv', file($data['file']));
                $schedule = $this->parseFile($csv);

                if (!empty($schedule)) {
                    if ($this->createSchedule($data['start'], $data['end'], $schedule)) {
                        $this->addFlash(
                            'notice',
                            'Schedule uploaded successfully for ' .
                            $data['start']->format('m/d/Y') . ' - ' . $data['end']->format('m/d/Y') . '!'
                        );
                        return $this->redirectToRoute('schedule');
                    } else {
                        $this->addFlash('error', 'Schedule failed to upload!');
                    }
                }
            } else {
                throw new FileNotFoundException();
            }
        }

        return $this->render('shared/form/form.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/schedule/timesheet/{date}", name="schedule_timesheet", requirements={"date": "\d{4}-\d{2}-\d{2}"})
     */
    public function scheduleTimeSheetAction($date = null) {
        $this->denyAccessUnlessGranted([
            'admin',
            'mentor',
            'schedule'
        ]);

        $d = \DateTime::createFromFormat('Y-m-d', $date);
        if (!($d && $d->format('Y-m-d') === $date)) {
            $d = new \DateTime();
        }
        if ($d->format('w') != 0) {
            $sunday = date('m/d/Y', strtotime('last Sunday', $d->getTimestamp()));;
        } else {
            $sunday = $d->format('m/d/Y');
        }
        $date_start = new \DateTime($sunday);
        $date_end = (new \DateTime($sunday))->add(new \DateInterval('P7D'));

        $date_period = new \DatePeriod($date_start, new \DateInterval('P1D'), $date_end);

        $timesheet = array();
        foreach ($date_period as $day) {
            $times = $this->getDoctrine()
                ->getRepository('App\Entity\Schedule\Timesheet')
                ->findByUserAndDay($this->getUser(), $day);
            $timesheet[] = array(
                'day' => $day,
                'times' => $times
            );
        }

        return $this->render('role/mentor/schedule/timesheet.html.twig', array(
            'timesheet' => $timesheet
        ));
    }

    //TODO add error messages
    private function parseFile($csv) {
        $logger = $this->get('logger');
        $schedule = array();
        // extract subjects
        $subjects = array_filter($csv [0]);
        array_splice($subjects, 0, 1);
        $num_subjects = count($subjects); // number of subjects
        $rooms = array_filter($csv [1]);
        array_splice($rooms, 0, 1);
        $days_row = $csv [2];
        foreach ($days_row as $day) {
            if (!empty($day)) {
                $days [] = $day;
                $schedule [$day] = array();
            }
        }
        for ($i = 3; $i < count($csv); $i++) {
            $row = $csv [$i];
            for ($col = 0; $col < 22; $col++) { // 22 is the number of columns in the csv file, count it if you don't believe me
                if (($col % 3 == 0) && !empty($row [$col])) { // get the times per day
                    $schedule [$days [$col / 3]] [$row [$col]] = array();
                }
                if ($col % 3 == 1) {
                    if (!empty($row [$col - 1])) { // time slot is previous column
                        $time = $row [$col - 1];
                    } else { // get the time slot, on previous column and row(s)
                        $b = $i - 1;
                        while (empty($csv [$b] [$col - 1])) {
                            $b--;
                        }
                        $time = $csv [$b] [$col - 1];
                    }
                    // put in subject and mentors
                    if (!empty($row [$col]) && !empty($row[$col + 1])) {
                        $schedule [$days [$col / 3]] [$time] [$row [$col]] ['mentors'] = explode(
                            ',',
                            $row [$col + 1]
                        );
                        $schedule [$days [$col / 3]] [$time] [$row [$col]] ['room'] = $rooms [array_search(
                            $row [$col],
                            $subjects
                        )];
                    }
                }
            }
        }

        return $schedule;
    }

    //TODO add error messages
    //TODO does not create a shift if there is not a mentor assigned
    private function createSchedule(\DateTime $start, \DateTime $end, array $schedule) {
        // to report new entities
        $new_entities = array(
            'rooms' => array(),
            'times' => array(),
            'subjects' => array(),
            'shifts' => array()
        );

        // to report unrecognized users
        $unrecognized_users = array();

        $new_schedule = $this->getDoctrine()
            ->getRepository(Schedule::class)
            ->findAll();
        if (empty($new_schedule)) {
            $new_schedule = new Schedule();
        } else {
            $new_schedule = $new_schedule[0];
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($new_schedule);
        $em->flush();

        $days = array();
        foreach ($schedule as $day => $times) {
            $days[$day] = array(
                'shifts' => array(),
                'dates' => array()
            );
            foreach ($times as $time_string => $subjects) {
                foreach ($subjects as $subject_string => $info) {
                    $start_end = explode('-', $time_string);

                    $room_info = explode(' ', $info['room']);
                    $room_numbers = explode('.', $room_info[1]);
                    $building = $room_info[0];
                    $floor = $room_numbers[0];
                    $number = $room_numbers[1];

                    $room = $this->getDoctrine()
                        ->getRepository(Room::class)
                        ->findOneBy(array(
                            'building' => $building,
                            'floor' => $floor,
                            'number' => $number
                        ));

                    //create room if it doesn't exist
                    if (!$room) {
                        $room = new Room();
                        $room->setActive(true);
                        $room->setBuilding($building);
                        $room->setFloor($floor);
                        $room->setNumber($number);
                        $room->setDescription($building . ' ' . $floor . '.' . $number);
                        $room->setCapacity(20);
                        $em->persist($room);
                        $em->flush();

                        // add to new entities list
                        $new_entities['rooms'][] = $room;
                    }

                    $mentors = array();
                    foreach ($info['mentors'] as $index => $mentor) {
                        $info['mentors'][$index] = trim($mentor);
                    }

                    foreach ($info['mentors'] as $username) {
                        if (!empty($username)) {
                            $mentor = $this->getDoctrine()
                                ->getRepository('App\Entity\User\User')
                                ->findOneByUsername($username);

                            $subject = $this->getDoctrine()
                                ->getRepository('Subject.php')
                                ->findOneByName($subject_string);
                            // create shiftsubject if it doesn't exist
                            if (!$subject) {
                                $subject = new Subject();
                                $subject->setName($subject_string);
                                //$subject->setAbbreviation('');
                                $subject->setShowOnCalendar(true);
                                $em->persist($subject);
                                $em->flush();

                                // add to new entities list
                                $new_entities['subjects'][] = $subject;
                            }

                            $time = $this->getDoctrine()
                                ->getRepository('App\Entity\Schedule\ShiftTime')
                                ->findOneBy(array(
                                    'startTime' => (new \DateTime($start_end[0])),
                                    'endTime' => (new \DateTime($start_end[1]))
                                ));

                            // create shifttime if it doesn't exist
                            if (!$time) {
                                $time = new ShiftTime();
                                $time->setStartTime(new \DateTime($start_end[0]));
                                $time->setEndTime(new \DateTime($start_end[1]));
                                $em->persist($time);
                                $em->flush();

                                // add to new entities list
                                $new_entities['times'][] = $time;
                            }

                            $day_of_week = date('w', strtotime($day));

                            //create shifts
                            $shift = $this->getDoctrine()
                                ->getRepository('App\Entity\Schedule\Shift')
                                ->findOneBy(array(
                                    'time' => $time,
                                    'subject' => $subject,
                                    'room' => $room,
                                    'day' => $day_of_week
                                ));

                            if (!$shift) {
                                $shift = new Shift();
                                $shift->setTime($time);
                                $shift->setSubject($subject);
                                $shift->setRoom($room);
                                $shift->setDay($day_of_week);
                                $em->persist($shift);
                                $em->flush();

                                // add to new entities list
                                $new_entities['shifts'][] = $shift;
                            }

                            if ($mentor) {
                                $shift_associated = array(
                                    'shift' => $shift,
                                    'mentor' => $mentor
                                );
                                $days[$day]['shifts'][] = $shift_associated;
                            } else {
                                // add to new unrecognized user list
                                $unrecognized_users[] = $username;
                            }
                        }
                    }
                }
            }
        }

        // Create scheduled shifts
        $end = $end->modify('+1 day');
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);

        //TODO get current shifts between dates
        foreach ($period as $date) {
            $days[$date->format('l')]['dates'][] = $date;
        }

        foreach ($days as $name => $day) {
            foreach ($day['dates'] as $date) {
                $assignments = array();
                foreach ($day['shifts'] as $shift) {
                    $new_shift = $this->getDoctrine()
                        ->getRepository('App\Entity\Schedule\ScheduledShift')
                        ->findOneBy(array(
                            'date' => $date,
                            'shift' => $shift['shift'],
                            'schedule' => $new_schedule
                        ));
                    if (!$new_shift) {
                        $new_shift = new ScheduledShift();
                        $new_shift->setDate($date);
                        $new_shift->setShift($shift['shift']);
                        $new_shift->setSchedule($new_schedule);
                        $em->persist($new_shift);
                        $em->flush();
                    }

                    $shift_assignment = $this->getDoctrine()
                        ->getRepository('App\Entity\Schedule\ShiftAssignment')
                        ->findOneBy(array(
                            'scheduledShift' => $new_shift,
                            'mentor' => $shift['mentor']
                        ));

                    if (!$shift_assignment) {
                        $shift_assignment = new ShiftAssignment();
                        $shift_assignment->setScheduledShift($new_shift);
                        $shift_assignment->setMentor($shift['mentor']);
                        $em->persist($shift_assignment);
                        $em->flush();
                    }

                    $assignments[] = $shift_assignment;
                }

                $scheduled_shifts = $this->getDoctrine()
                    ->getRepository('App\Schedule:ScheduledShift')
                    ->findByDate($date);
                foreach ($scheduled_shifts as $shift) {
                    foreach ($shift->getMentors() as $assignment) {
                        if (!in_array($assignment, $assignments)) {
                            if ($em->getRepository('App\Schedule:Absence')->findOneBySubstitute($assignment)) {
                                continue;
                            }
                            if ($assignment->getAbsence()) {
                                $assignment->setAbsence(null);
                            }
                            $shift->removeMentor($assignment);
                            $em->remove($assignment);
                        }
                    }
                }
            }
        }
        $em->flush();

        // report new entities and unrecognized users
        if (!empty($new_entities['shifts'])) {
            $this->addFlash('notice', 'New shifts created: ' . count($new_entities['shifts']));
        }
        if (!empty($new_entities['rooms'])) {
            $this->addFlash('notice', 'New rooms created: ' . implode(", ", $new_entities['rooms']));
        }
        if (!empty($new_entities['times'])) {
            $this->addFlash('notice', 'New times created: ' . implode(", ", $new_entities['times']));
        }
        if (!empty($new_entities['subjects'])) {
            $this->addFlash('notice', 'New subjects created: ' . implode(", ", $new_entities['subjects']));
        }
        if (!empty($unrecognized_users)) {
            $this->addFlash('warning', 'Unrecognized users: ' . implode(", ", $unrecognized_users));
        }

        return true;
    }
}