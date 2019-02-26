<?php

namespace App\Controller;


use App\Entity\Event\Event;
use App\Entity\Schedule\Shift;
use App\Entity\Schedule\Timesheet;
use App\Entity\Session\Quiz;
use App\Entity\Session\QuizTimeSlot;
use App\Entity\Session\Session;
use App\Entity\Session\SessionTimeSlot;
use App\Entity\Session\TimeSlot;
use App\Entity\Session\WalkInAttendance;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DisplayController extends Controller {
    /**
     * @Route("/display", name="display")
     */
    public function displayAction() {
        $em = $this->getDoctrine()->getManager();
        //mentors
        $repo = $em->getRepository(Timesheet::class);
        $qb = $repo->createQueryBuilder('t');
        $qb->where($qb->expr()->isNull('t.timeOut'))
            ->andWhere($qb->expr()->gt('t.timeIn', ':date'))
            ->setParameter('date', (new \DateTime())->setTime(0, 0));
        $query = $qb->getQuery();
        $ins = $query->getResult();
        $mentors = array();
        foreach($ins as $in) {
            $mentors[] = $in->getUser();
        }

        //students
        $repo = $em->getRepository(WalkInAttendance::class);
        $qb = $repo->createQueryBuilder('a');
        $qb->where($qb->expr()->isNull('a.timeOut'))
            ->andWhere($qb->expr()->gte('a.timeIn', ':date'))
            ->orderBy('a.timeIn', 'ASC')
            ->setParameter('date', (new \DateTime())->setTime(0, 0));
        $query = $qb->getQuery();
        $students = $query->getResult();

        $first_day = new \DateTime();
        $first_day->format('Y-m-d H:i:s');
        $first_day->setTime(0, 0);

        $last_day = new \DateTime();
        $last_day->add(new \DateInterval('PT72H'));
        $last_day->setTime(11, 59);

        //sessions
        $repo = $em->getRepository(SessionTimeSlot::class);
        $qb = $repo->createQueryBuilder('t');
        $qb->where($qb->expr()->isNull('t.actualEndTime'))
            ->andWhere($qb->expr()->gte('t.startTime', ':first_date'))
            ->andWhere($qb->expr()->lte('t.startTime', ':last_date'))
            ->orderBy('t.startTime', 'ASC')
            ->setParameter('first_date', $first_day)
            ->setParameter('last_date', $last_day);
        $query = $qb->getQuery();

        $sessions = $query->getResult();

        //Get shift leader
        $todayWeekday = date("w");
        $repo = $em->getRepository(Shift::class);
        $qb = $repo->createQueryBuilder('sh')->join('sh.schedule', 'sc')->join('sc.semester', 'se');
        $qb->where('se.active = :active')
            ->andWhere('sh.day = :todays_weekday')
            ->andWhere($qb->expr()->lte('sh.startTime', ':now'))
            ->andWhere($qb->expr()->gt('sh.endTime', ':now'))
            ->setParameter('todays_weekday', $todayWeekday)
            ->setParameter('now', new \DateTime())
            ->setParameter('active', true);

        $query = $qb->getQuery();

        $shift = $query->getOneOrNullResult();
        $shift_leader = $shift->getShiftLeader();

        //Get quizzes
        $repo = $em->getRepository(Quiz::class);
        $qb = $repo->createQueryBuilder('q')->join('q.timeSlot', 't');
        $qb->where($qb->expr()->lte('t.startTime', ':now'))
            ->andWhere($qb->expr()->gt('t.endTime', ':now'))
            ->setParameter('now', new \DateTime());

        $query = $qb->getQuery();
        $quizzes = $query->getResult();

        return $this->render('shared/display.html.twig', array(
            'mentors' => $mentors,
            'students' => $students,
            'sessions' => $sessions,
            'quizzes' => $quizzes,
            'shift_leader' => $shift_leader
        ));
    }
}