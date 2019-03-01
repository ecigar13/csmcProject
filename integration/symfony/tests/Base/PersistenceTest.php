<?php


namespace App\Tests\Base;


use App\Tests\TestUtils\DatabaseInitializer;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class PersistenceTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Boots up the application kernel (necessary to use persistence) and initializes database schema.
     */
    public static function setUpBeforeClass()
    {
        self::bootKernel();

        DatabaseInitializer::initialize(self::$kernel);
    }

    /**
     * Initializes @see entityManager and begins a transaction.
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function setUp()
    {
        $this->entityManager = DatabaseInitializer::getEntityManager(self::$kernel);
        $this->entityManager->clear();
        $this->entityManager->beginTransaction();
        $this->createTestData();

        $this->entityManager->flush();
    }

    /**
     * Rolls back the transaction started in @see setUp.
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    protected function tearDown()
    {
        $this->entityManager->getConnection()->rollBack();
    }

    /**
     * Should use the @see entityManager to persist a set of test data that will be available for every test case.
     * There is no need to flush the @see entityManager here.
     */
    protected abstract function createTestData();

    /**
     * Asserts that two arrays of entities are equal by comparing the IDs only.
     *
     * @param array $expected
     * @param array $actual
     * @param string|null $message
     */
    protected function assertEqualsByIDs(array $expected, array $actual, string $message = null)
    {
        $idExtractor = function ($x) {
            return $x->getId();
        };

        self::assertEquals(array_map($idExtractor, $expected), array_map($idExtractor, $actual), $message,
            0, 1, true);
    }

}