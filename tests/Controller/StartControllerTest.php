<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StartControllerTest extends WebTestCase
{
    public function testIndexNoLocale()
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects('/en/');
    }
}
