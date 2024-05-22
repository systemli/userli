<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegister(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $voucher = $crawler->filter('input#registration_voucher')->first();
        $this->assertNotNull($voucher);
        $this->assertNull($voucher->attr('readonly'));
    }

    public function testRegisterWithVoucher(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register/161161');

        $voucher = $crawler->filter('input#registration_voucher')->first();
        $this->assertNotNull($voucher);
        $this->assertNotNull($voucher->attr('readonly'));
    }
}
