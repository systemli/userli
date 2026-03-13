<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Domain;
use App\Entity\OpenPgpKey;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class OpenPgpKeyTest extends TestCase
{
    public function testGetUploaderReturnsNullByDefault(): void
    {
        $key = new OpenPgpKey();

        self::assertNull($key->getUploader());
    }

    public function testSetAndGetUploader(): void
    {
        $key = new OpenPgpKey();
        $user = new User('alice@example.org');

        $key->setUploader($user);

        self::assertSame($user, $key->getUploader());
    }

    public function testSetUploaderToNull(): void
    {
        $key = new OpenPgpKey();
        $user = new User('alice@example.org');

        $key->setUploader($user);
        $key->setUploader(null);

        self::assertNull($key->getUploader());
    }

    public function testGetWkdHashReturnsNullByDefault(): void
    {
        $key = new OpenPgpKey();

        self::assertNull($key->getWkdHash());
    }

    public function testSetAndGetWkdHash(): void
    {
        $key = new OpenPgpKey();

        $key->setWkdHash('abc123');

        self::assertSame('abc123', $key->getWkdHash());
    }

    public function testGetDomainReturnsNullByDefault(): void
    {
        $key = new OpenPgpKey();

        self::assertNull($key->getDomain());
    }

    public function testSetAndGetDomain(): void
    {
        $key = new OpenPgpKey();
        $domain = new Domain();
        $domain->setName('example.org');

        $key->setDomain($domain);

        self::assertSame($domain, $key->getDomain());
    }
}
