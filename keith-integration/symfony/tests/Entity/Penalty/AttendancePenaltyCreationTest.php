<?php

namespace App\Tests\Entity\Penalty;

use App\Entity\Penalty\AbsenceWithNoticePenalty;
use App\Entity\Penalty\AttendancePenalty;
use App\Entity\Penalty\TardinessPenalty;
use App\Tests\Base\PersistenceTest;
use App\Utils\AttendancePenaltyPersistenceManager;

/**
 * Tests creation of @see AttendancePenalty entities.
 * @see AttendancePenaltyPersistenceManagerTest for persistence tests.
 *
 * @package App\Tests\Entity\Penalty
 */
class AttendancePenaltyCreationTest extends PersistenceTest
{
    /**
     * @var AttendancePenaltyPersistenceManager
     */
    private $manager;

    /**
     * Tests various types of invalid parameters.
     *
     * @dataProvider createInvalidTardinessPenaltiesData
     * @param array $penaltyAmounts
     * @param array $intervalBounds
     */
    public function testCreateInvalidTardinessPenalties(array $penaltyAmounts = null, array $intervalBounds = null)
    {
        $this->expectException(\LogicException::class);

        // There should be no way to create an error with the firstCumulative parameter
        $this->manager->createTardinessPenalties($penaltyAmounts, $intervalBounds);
    }

    public function createInvalidTardinessPenaltiesData()
    {
        $singleton = [1];
        $lengthFour = [1, 1, 1, 1];

        $lengthTwo = [1, 2];
        return array(
            // If one is empty they must both be empty
            [null, $singleton],
            [$singleton, null],
            [null, $lengthTwo],
            [$lengthTwo, null],
            [[], $singleton],
            [$singleton, []],
            [[], $lengthTwo],
            [$lengthTwo, []],
            // They must be the same length
            [$singleton, $lengthTwo],
            [$lengthTwo, $singleton],
            [$singleton, [1, 2, 3]],
            [[1, 2, 3], $singleton],
            // Interval bounds must be in ascending order
            [$lengthFour, [4, 3, 2, 1]],
            [$lengthFour, [1, 3, 2, 4]],
            [$lengthFour, [10, 4, 1000, 40]],
            // Interval bounds must have no repeated elements
            [$lengthFour, [1, 1, 1, 1]],
            [$lengthFour, [2, 4, 4, 8]],
            [$lengthFour, [1, 1, 2, 2]],
            [$lengthFour, [1, 2, 3, 3]],
            [$lengthFour, [6, 10, 10, 20]],
            // First boundary amount must not be zero
            [$lengthFour, [0, 0, 0, 0]],
            [$lengthFour, [0, 2, 4, 6]]
        );
    }

    /**
     * @dataProvider createTardinessPenaltiesData
     * @param TardinessPenalty[] $expectedPenalties
     */
    public function testCreateTardinessPenalties(array $expectedPenalties = null)
    {
        // Consider the empty case separately
        if ($expectedPenalties == null) {
            $this->manager->createTardinessPenalties(null, null);
            self::assertNull($this->manager->getTardinessPenalties());

            return;
        } elseif (count($expectedPenalties) == 0) {
            $this->manager->createTardinessPenalties([], []);
            self::assertNull($this->manager->getTardinessPenalties());

            return;
        }

        $penaltyAmounts = array();
        $intervalBounds = array();
        foreach ($expectedPenalties as $penalty) {
            $penaltyAmounts[] = $penalty->getPenaltyAmount();
            $intervalBounds[] = $penalty->getEndingMinutes();
        }

        $firstCumulative = $expectedPenalties[0]->isCumulative();
        $this->manager->createTardinessPenalties($penaltyAmounts, $intervalBounds, $firstCumulative);

        self::assertEquals($expectedPenalties, $this->manager->getTardinessPenalties());
    }

    public function createTardinessPenaltiesData()
    {
        return array(
            [null],
            [[]],
            [array(
                new TardinessPenalty(0, 0, 5, true)
            )],
            [array(
                new TardinessPenalty(1, 0, 5, false)
            )],
            [array(
                new TardinessPenalty(0, 0, 4, true),
                new TardinessPenalty(2, 4, 10, false)
            )],
            [array(
                new TardinessPenalty(1, 0, 4, false),
                new TardinessPenalty(2, 4, 10, false)
            )],
            [array(
                new TardinessPenalty(1, 0, 8, false),
                new TardinessPenalty(2, 8, 15, false),
                new TardinessPenalty(3, 15, 20, false)
            )],
            [array(
                new TardinessPenalty(0, 0, 8, true),
                new TardinessPenalty(2, 8, 15, false),
                new TardinessPenalty(3, 15, 20, false)
            )],
            // Test the actual model
            [array(
                new TardinessPenalty(0, 0, 5, true),
                new TardinessPenalty(0.5, 5, 15, false),
                new TardinessPenalty(1, 15, 30, false)
            )]
        );
    }

    /**
     * @dataProvider createCoveredShiftBonusData
     * @param float $expectedBonus
     */
    public function testCreateShiftCoveredBonus(float $expectedBonus = null)
    {
        $this->manager->createShiftCoveredBonus($expectedBonus);

        if ($expectedBonus != null) {
            self::assertEquals($expectedBonus, $this->manager->getShiftCoveredBonus()->getPenaltyAmount());
        } else {
            self::assertNull($this->manager->getShiftCoveredBonus());
        }
    }

    public function createCoveredShiftBonusData()
    {
        return [
            [null],
            [1],
            [2],
            [10],
            [1000.5],
            [10000.8]
        ];
    }

    /**
     * @dataProvider createCoveredShiftBonusData
     * @param float $expectedBonus
     */
    public function testCreateClaimShiftBonus(float $expectedBonus = null)
    {
        $this->manager->createClaimShiftBonus($expectedBonus);

        if ($expectedBonus != null) {
            self::assertEquals($expectedBonus, $this->manager->getClaimShiftBonus()->getPenaltyAmount());
        } else {
            self::assertNull($this->manager->getClaimShiftBonus());
        }
    }

    /**
     * @dataProvider createAbsenceWithoutNoticePenaltyData
     * @param float $expectedPenalty
     */
    public function testCreateAbsenceWithoutNoticePenalty(float $expectedPenalty = null)
    {
        $this->manager->createAbsenceWithoutNoticePenalty($expectedPenalty);

        if ($expectedPenalty != null) {
            self::assertEquals($expectedPenalty, $this->manager->getAbsenceWithoutNoticePenalty()->getPenaltyAmount());
        } else {
            self::assertNull($this->manager->getAbsenceWithoutNoticePenalty());
        }
    }

    public function createAbsenceWithoutNoticePenaltyData()
    {
        return [
            [null],
            [1],
            [1.5],
            [5],
            [5.5],
            [10],
            [1000],
            [1000.8]
        ];
    }

    /**
     * We test the invalid case for justified and unjustified with a single method because they are both implemented the
     * same way.
     *
     * @dataProvider createInvalidAbsenceWithNoticePenaltiesData
     * @param bool $isJustified
     * @param array|null $penaltyAmounts
     * @param array|null $intervalBounds
     */
    public function testCreateInvalidAbsenceWithNoticePenalties(bool $isJustified, array $penaltyAmounts = null, array $intervalBounds = null)
    {
        $this->expectException(\LogicException::class);

        if ($isJustified) {
            $this->manager->createJustifiedAbsenceWithNoticePenalties($penaltyAmounts, $intervalBounds);
        } else {
            $this->manager->createUnjustifiedAbsenceWithNoticePenalties($penaltyAmounts, $intervalBounds);
        }
    }

    public function createInvalidAbsenceWithNoticePenaltiesData()
    {
        $singleton = [1];
        $threeElements = [1, 2, 3];
        $fourElements = [2, 4, 6, 8];

        return array(
            // If penalty amounts is empty, interval bounds must be empty
            [true, null, $singleton],
            [false, null, $singleton],
            [true, [], $singleton],
            [false, [], $singleton],
            // Size of interval bounds must be one less than the size of the penalties
            [true, $singleton, $threeElements],
            [false, $singleton, $threeElements],
            [true, $threeElements, $threeElements],
            [false, $threeElements, $threeElements],
            [true, $threeElements, $fourElements],
            [false, $threeElements, $fourElements],
            // Interval bounds must be in ascending order
            [true, $fourElements, [3, 2, 1]],
            [false, $fourElements, [3, 2, 1]],
            [true, $fourElements, [100, 101, 1]],
            [false, $fourElements, [100, 101, 1]],
            // Interval bounds must contain no duplicated elements
            [true, $fourElements, [1, 1, 1]],
            [false, $fourElements, [1, 1, 1]],
            [true, $fourElements, [5, 6, 6]],
            [false, $fourElements, [5, 6, 6]]
        );
    }

    /**
     * Test both justified and unjustified at the same time because they are implemented the same way.
     *
     * @dataProvider createAbsenceWithNoticePenaltiesData
     * @param bool $isJustified
     * @param AbsenceWithNoticePenalty[]|null $expectedPenalties
     */
    public function testCreateAbsenceWithNoticePenalties(bool $isJustified, array $expectedPenalties = null)
    {
        // Test empty case separately
        if ($expectedPenalties == null || count($expectedPenalties) == 0) {

            if ($expectedPenalties == null) {
                $arg = null;
            } else {
                $arg = [];
            }

            if ($isJustified) {
                $this->manager->createJustifiedAbsenceWithNoticePenalties($arg, $arg);
                $result = $this->manager->getJustifiedAbsenceWithNoticePenalties();
            } else {
                $this->manager->createUnjustifiedAbsenceWithNoticePenalties($arg, $arg);
                $result = $this->manager->getUnjustifiedAbsenceWithNoticePenalties();
            }

            self::assertNull($result);

            return;
        }

        $penaltyAmounts = array();
        $intervalBounds = array();

        foreach (array_slice($expectedPenalties, 0, -1) as $penalty) {
            $penaltyAmounts[] = $penalty->getPenaltyAmount();
            $intervalBounds[] = $penalty->getHoursBefore();
        }

        // Penalty amounts has one more element than interval bounds
        $lastPenalty = array_values(array_slice($expectedPenalties, -1))[0];
        $penaltyAmounts[] = $lastPenalty->getPenaltyAmount();

        if ($isJustified) {
            $this->manager->createJustifiedAbsenceWithNoticePenalties($penaltyAmounts, $intervalBounds);
            $result = $this->manager->getJustifiedAbsenceWithNoticePenalties();
        } else {
            $this->manager->createUnjustifiedAbsenceWithNoticePenalties($penaltyAmounts, $intervalBounds);
            $result = $this->manager->getUnjustifiedAbsenceWithNoticePenalties();
        }

        self::assertEquals($expectedPenalties, $result);
    }

    public function createAbsenceWithNoticePenaltiesData()
    {
        return array(
            // Test empty penalties first
            [true, null],
            [false, null],
            [true, []],
            [false, []],
            // Test other cases
            [true, array(
                new AbsenceWithNoticePenalty(5, true, null)
            )],
            [false, array(
                new AbsenceWithNoticePenalty(5, false, null)
            )],
            [true, array(
                new AbsenceWithNoticePenalty(9, true, 12),
                new AbsenceWithNoticePenalty(1, true, null)
            )],
            [false, array(
                new AbsenceWithNoticePenalty(9, false, 12),
                new AbsenceWithNoticePenalty(1, false, null)
            )],
            [true, array(
                new AbsenceWithNoticePenalty(20.4, true, 12),
                new AbsenceWithNoticePenalty(10.8, true, 24),
                new AbsenceWithNoticePenalty(5.5, true, null)
            )],
            [false, array(
                new AbsenceWithNoticePenalty(20.4, false, 12),
                new AbsenceWithNoticePenalty(10.8, false, 24),
                new AbsenceWithNoticePenalty(5.5, false, null)
            )],
            // Test the actual model
            [true, array(
                new AbsenceWithNoticePenalty(2, true, 24),
                new AbsenceWithNoticePenalty(1, true, null)
            )],
            [false, array(
                new AbsenceWithNoticePenalty(4, false, 24),
                new AbsenceWithNoticePenalty(3, false, null)
            )]
        );
    }

    /**
     * @inheritdoc
     */
    protected function createTestData()
    {
        // This class is only meant to be instantiated using an entity manager
        $this->manager = AttendancePenaltyPersistenceManager::loadModel($this->entityManager);

        // Ensure everything is null
        if ($this->manager->getAbsenceWithoutNoticePenalty() != null ||
            $this->manager->getShiftCoveredBonus() != null ||
            $this->manager->getJustifiedAbsenceWithNoticePenalties() != null ||
            $this->manager->getUnjustifiedAbsenceWithNoticePenalties() != null ||
            $this->manager->getTardinessPenalties() != null) {
            throw new \LogicException('Manager must have no initial data');
        }

        // We need no data in the database
    }
}
