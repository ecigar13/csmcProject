<?php


namespace App\Tests\TestUtils;


use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpKernel\KernelInterface;

class DatabaseInitializer
{
    public static function initialize(KernelInterface $kernel)
    {
        // Make sure we are in the test environment
        if ('test' !== $kernel->getEnvironment()) {
            throw new \LogicException('Initializer must be executed in the test environment');
        }

        // Get the entity manager from the service container
        $entityManager = self::getEntityManager($kernel);

        // Run the schema update tool using our entity metadata
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metadata);
    }

    /**
     * @param KernelInterface $kernel
     * @return \Doctrine\ORM\EntityManager
     */
    public static function getEntityManager(KernelInterface $kernel)
    {
        $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        return $entityManager;
    }

}