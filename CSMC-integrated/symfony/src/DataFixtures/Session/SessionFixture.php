<?php

namespace App\DataFixtures\Session;

use App\DataFixtures\Schedule\ScheduleFixture;
use App\DataFixtures\User\UserFixture;
use App\Entity\Session\Quiz;
use App\Entity\Session\Request;
use App\Entity\Session\Review;
use App\Entity\Session\Session;
use App\Entity\Session\SessionTimeSlot;
use App\DataFixtures\Misc\RoomFixture;
use App\DataFixtures\Course\SectionFixture;
use App\Entity\Session\ScheduledSession;
use App\Entity\Session\SessionType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;

class SessionFixture extends Fixture implements DependentFixtureInterface {
    private $logger;

    public function __construct(LoggerInterface $logger){
        $this->logger = $logger;
    }

    public function load(ObjectManager $manager) {
         $review_type = new SessionType('review', '#ff00ff');
         $rework_type = new SessionType('rework', '#00ffff');
         $quiz_type = new SessionType('quiz', '#ffff00');

         $manager->persist($review_type);
         $manager->persist($rework_type);
         $manager->persist($quiz_type);

         for($i = 0; $i < 20; $i++) {
             // $request = new Request($review_type,
             //     $this->getReference(UserFixture::INSTRUCTOR_00),
             //     'request ' . $i,
             //     new \DateTime(),
             //     new \DateTime(),
             //     'do stuff',
             //     [
             //         $this->getReference(SectionFixture::CS_3305 . '001'),
             //         $this->getReference(SectionFixture::CS_3305 . '002'),
             //     ]);
             // $manager->persist($request);
         }

         $room = $this->getReference(RoomFixture::ECSS_4415);
         $repeats = 10;
         $capacity = 50;

         $schedule = $this->getReference(ScheduleFixture::SCHEDULE);
         $scheduledShifts = $schedule->getScheduledShifts();

 //         for ($i = 0; $i < 20; $i++) {
 //             $session = new ScheduledSession($review_type, 'session ' . $i, $repeats,'desc', 'student', 'mentor', true, false);
 //             $session->setDefaults($room, $capacity, new \DateInterval('PT1H15M'));
 //             $session->addSection($this->getReference(SectionFixture::CS_3305 . '001'));
 //
 //             for($j = 0; $j < $repeats; $j++) {
 //                 $start = new \DateTime();
 //                 $start->setTime(10, 0, 0);
 //                 $start->add(new \DateInterval('P' . $i . 'DT' . $j . 'H'));
 //                 $end = new \DateTime();
 //                 $end->setTime(10, 0, 0);
 //                 $end->add(new \DateInterval('P'. $i . 'DT' . ($j + 1) . 'H'));
 //
 //                 $ts = new SessionTimeSlot($session, $room, $start, $end, $capacity);
 //                 $session->addTimeSlot($ts);
 //
 //                 for($s = 0; $s < $ts->getCapacity() / 2; $s++) {
 //                     $n = str_pad($s, 6, '0', STR_PAD_LEFT);
 //                     $user = $this->getReference(UserFixture::STUDENT . $n);
 //
 //                     $ts->register($user);
 //                 }
 //
 //                 foreach ($scheduledShifts as $ss) {
 //                     $scheduledDate = new \DateTime($ss->getDate()->format('m/d/Y'));
 //                     $startTime = $ss->getShift()->getStartTime();
 //                     $scheduledDate->setTime($startTime->format('H'), $startTime->format('i'));
 //
 //                     if(($scheduledDate == $start) && ($ss->getAssignments()->count()) > 0) {
 //
 //                         $numberOfMentors = rand(1,3);
 //                         for($k = 0; $k < $numberOfMentors; $k++) {
 //                             $ts->assign($ss->getAssignments()->get($k));
 //                         }
 //
 // //                        $this->logger->info($scheduledDate->format('m/d/y h:i') . ' count: ' .$ss->getAssignments()->count());
 //                     }
 //                 }
 //
 //             }
 //
 //             $manager->persist($session);
 //         }

         // $start = new \DateTime();
         // $end = new \DateTime();
         // $end->add(new \DateInterval('P10D'));
         // $quiz = new Quiz($quiz_type,'session ' . $i, $room, $start, $end, 'desc', 'student', 'mentor', true, true);
         // $manager->persist($quiz);

         $manager->flush();
    }

    public function getDependencies() {
        return array(
            RoomFixture::class,
            UserFixture::class,
            ScheduleFixture::class
        );
    }
}