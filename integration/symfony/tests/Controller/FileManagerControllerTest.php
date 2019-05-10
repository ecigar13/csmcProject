<?php

namespace App\Tests\ApplicationAvailabilityFunctionalTest;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FileManagerControllerTest extends WebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testPageIsSuccessful($url)
    {
        $client = self::createClient();
        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function urlProvider()
    {
        yield ['/'];
        yield ['/admin'];
        yield ['/fms'];
    }
}

?>