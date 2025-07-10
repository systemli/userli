<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegisterShow(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $voucher = $crawler->filter('input#registration_voucher')->first();
        $this->assertNotNull($voucher);
        $this->assertNull($voucher->attr('readonly'));
    }

    public function testRegisterWithVoucherShow(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register/161161');

        $this->assertResponseIsSuccessful();
        $voucher = $crawler->filter('input#registration_voucher')->first();
        $this->assertNotNull($voucher);
        $this->assertNotNull($voucher->attr('readonly'));
    }
}
