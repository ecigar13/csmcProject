<?php

namespace App\Tests\Entity\Schedule;

use App\Entity\Misc\Room;
use App\Entity\Misc\Semester;
use App\Entity\Schedule\Schedule;
use App\Entity\Schedule\Shift;
use PHPUnit\Framework\TestCase;

class ShiftTest extends TestCase
{

    private $shift1;
    private $shift2;

    /**
     * @dataProvider createValidSignInData
     * @param int $expected
     * @param Shift $shift
     * @param \DateTime $signInDateTime
     */
    public function testCalculateMinutesLateSignIn(int $expected, Shift $shift, \DateTime $signInDateTime)
    {
        self::assertEquals($expected, $shift->calculateTardinessMinutesForSignIn($signInDateTime));
    }

    public function createValidSignInData()
    {
        $this->createObjects();

        return array(
            // Dates should be ignored
            [1, $this->shift1, new \DateTime('1970-01-01 09:01')],
            [1, $this->shift2, new \DateTime('1970-01-01 14:01')],
            [5, $this->shift1, new \DateTime('2000-05-05 09:05')],
            [5, $this->shift2, new \DateTime('2010-09-25 14:05')],
            // 0 is a valid return value
            [0, $this->shift1, new \DateTime('09:00')],
            [0, $this->shift2, new \DateTime('14:00')],
            [0, $this->shift1, new \DateTime('09:00:59')],
            [0, $this->shift2, new \DateTime('14:00:59')],
            // Middle values
            [60, $this->shift1, new \DateTime('10:00')],
            [60, $this->shift2, new \DateTime('15:00')],
            // Almost end of shift
            [119, $this->shift1, new \DateTime('10:59:59')],
            [119, $this->shift2, new \DateTime('15:59:59')],
            [119, $this->shift1, new \DateTime('10:59')],
            [119, $this->shift2, new \DateTime('15:59')]
        );
    }

    /**
     * @dataProvider createInvalidSignInData
     * @param Shift $shift
     * @param \DateTime $signInDateTime
     */
    public function testCalculateInvalidMinutesLateSignIn(Shift $shift, \DateTime $signInDateTime)
    {
        $this->expectException(\InvalidArgumentException::class);

        $shift->calculateTardinessMinutesForSignIn($signInDateTime);
    }

    public function createInvalidSignInData()
    {
        $this->createObjects();

        return array(
            // Test the limits
            [$this->shift1, new \DateTime('08:59:59')],
            [$this->shift1, new \DateTime('11:00:01')],
            [$this->shift2, new \DateTime('13:59:59')],
            [$this->shift2, new \DateTime('16:00:01')],
            // Test other invalid values
            [$this->shift1, new \DateTime('00:00:00')],
            [$this->shift1, new \DateTime('23:59:59')],
            [$this->shift2, new \DateTime('00:00:00')],
            [$this->shift2, new \DateTime('23:59:59')],
        );
    }

    private function createObjects()
    {
        $semester = new Semester(Semester::SEASON_DEV, 2018, new \DateTime('2018-01-01'), new \DateTime('2018-05-01'), true);
        $room = new Room('Building', 1, 1, 'Desc', 20, true);
        $schedule = new Schedule($semester);

        $this->shift1 = new Shift($schedule, $room, new \DateTime('09:00'), new \DateTime('11:00'), 0);
        $this->shift2 = new Shift($schedule, $room, new \DateTime('14:00'), new \DateTime('16:00'), 0);
    }
}
