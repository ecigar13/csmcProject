<?php

namespace App\DataFixtures\Schedule;

use App\DataFixtures\Misc\RoomFixture;
use App\DataFixtures\Misc\SemesterFixture;
use App\DataFixtures\Misc\SubjectFixture;
use App\DataFixtures\User\UserFixture;
use App\Entity\Schedule\Schedule;
use App\Entity\Schedule\ScheduledShift;
use App\Entity\Schedule\Shift;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;

class ScheduleFixture extends Fixture implements DependentFixtureInterface {
    const SCHEDULE = 'schedule';
    public $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function load(ObjectManager $manager) {
        $semester = $this->getReference(SemesterFixture::ACTIVE);
        $room = $this->getReference(RoomFixture::ECSS_4415);

        $schedule = new Schedule($semester);
        $manager->persist($schedule);

        $user_0 = $this->getReference(UserFixture::MENTOR . '000000');
        $user_1 = $this->getReference(UserFixture::MENTOR . '000001');
        $user_2 = $this->getReference(UserFixture::MENTOR . '000002');
        $user_3 = $this->getReference(UserFixture::MENTOR . '000003');
        $user_4 = $this->getReference(UserFixture::MENTOR . '000004');
        $user_5 = $this->getReference(UserFixture::MENTOR . '000005');
        $user_6 = $this->getReference(UserFixture::MENTOR . '000006');
        $user_7 = $this->getReference(UserFixture::MENTOR . '000007');
        $subjects = array(
            array(
                'subject' => $this->getReference(SubjectFixture::JAVA),
                'max' => 3,
                'mentors' => array(
                    $user_0,
                    $user_1
                )
            ),
            array(
                'subject' => $this->getReference(SubjectFixture::CPP),
                'max' => 3,
                'mentors' => array(
                    $user_2,
                    $user_3
                )
            ),
            array(
                'subject' => $this->getReference(SubjectFixture::DISCRETE_MATH),
                'max' => 3,
                'mentors' => array(
                    $user_4,
                    $user_5
                )
            ),
            array(
                'subject' => $this->getReference(SubjectFixture::COMPUTER_ARCHITECTURE),
                'max' => 3,
                'mentors' => array(
                    $user_6,
                    $user_7
                )
            ),
        );

        // create shifts
        $shifts = array();

        $start = new \DateTime('10am');
        $end = new \DateTime('11:30am');

        for ($day = 1; $day < 6; $day++) {
            $shifts[$day] = array();
            $s = $start;
            $e = $end;
            for ($i = 0; $i < 8; $i++) {
                if ($i == 0) {
                    $s = new \DateTime($start->format('H:i'));
                    $e = new \DateTime($end->format('H:i'));
                } elseif ($i == 4 && $day == 5) {
                    $s = new \DateTime($e->format('H:i'));
                    $e = new \DateTime($e->format('H:i'));
                    $e->add(new \DateInterval('PT2H'));
                } elseif ($i == 7 && ($day == 1 || $day == 4)) {
                    continue;
                } else {
                    $s = new \DateTime($e->format('H:i'));
                    $e = new \DateTime($e->format('H:i'));
                    $e->add(new \DateInterval('PT1H30M'));
                }

                $shift = new Shift($schedule, $room, $s, $e, $day);

                $shift->assignShiftLeader($this->getReference(UserFixture::MENTOR . '000005'));

                foreach ($subjects as $subject) {
                    $shift->addSubject($subject['subject'], $subject['max']);
                    foreach ($subject['mentors'] as $mentor) {
                        $shift->addMentor($subject['subject'], $mentor);
                    }
                }

                $manager->persist($shift);

                $shifts[$day][] = $shift;
            }
        }

        $start->setTime(12, 0, 0);
        $end->setTime(2, 0, 0);

        $shifts[0] = array();
        $shifts[6] = array();
        for ($i = 0; $i < 3; $i++) {
            $s = new \DateTime($start->format('H:i'));
            $s->add(new \DateInterval('PT' . ($i * 2) . 'H'));
            $e = new \DateTime($start->format('H:i'));
            $e->add(new \DateInterval('PT' . ($i * 2) . 'H'));

            $shift_sun = new Shift($schedule, $room, $s, $e, 0);
            $shift_sat = new Shift($schedule, $room, $s, $e, 6);

            $shift_sun->assignShiftLeader($this->getReference(UserFixture::MENTOR . '000000'));
            $shift_sat->assignShiftLeader($this->getReference(UserFixture::MENTOR . '000000'));

            foreach ($subjects as $subject) {
                $shift_sun->addSubject($subject['subject'], $subject['max']);
                foreach ($subject['mentors'] as $mentor) {
                    $shift_sun->addMentor($subject['subject'], $mentor);
                }
            }

            foreach ($subjects as $subject) {
                $shift_sat->addSubject($subject['subject'], $subject['max']);
                foreach ($subject['mentors'] as $mentor) {
                    $shift_sat->addMentor($subject['subject'], $mentor);
                }
            }

            $manager->persist($shift_sun);
            $manager->persist($shift_sat);

            $shifts[0][] = $shift_sun;
            $shifts[6][] = $shift_sat;
        }

        // generate scheduled shifts and assignments
        $start_date = $semester->getStartDate();
        $end_date = $semester->getEndDate();
        $interval = new \DateInterval('P1D');

        $period = new \DatePeriod($start_date, $interval, $end_date);

        foreach ($period as $date) {
            $day = $date->format('w');

            $s = $shifts[$day];

            foreach ($s as $shift) {
                $scheduled_shift = new ScheduledShift($schedule, $shift, $date);

                $manager->persist($scheduled_shift);
            }
        }

        $manager->flush();

        $this->addReference(self::SCHEDULE, $schedule);
    }

    public function getDependencies() {
        return [
            SemesterFixture::class,
            RoomFixture::class,
            SubjectFixture::class,
            UserFixture::class
        ];
    }
}