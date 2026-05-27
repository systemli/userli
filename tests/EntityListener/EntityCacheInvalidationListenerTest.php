<?php

declare(strict_types=1);

namespace App\Tests\EntityListener;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\EntityListener\EntityCacheInvalidationListener;
use App\Service\Cache\EntityCacheInvalidator;
use App\Service\Cache\EntityCacheType;
use PHPUnit\Framework\TestCase;

class EntityCacheInvalidationListenerTest extends TestCase
{
    public function testOnUserChangeDispatchesUserType(): void
    {
        $user = new User('user@example.org');

        $invalidator = $this->createMock(EntityCacheInvalidator::class);
        $invalidator->expects(self::once())
            ->method('dispatch')
            ->with(EntityCacheType::USER, 'user@example.org');

        new EntityCacheInvalidationListener($invalidator)->onUserChange($user);
    }

    public function testOnAliasChangeDispatchesAliasType(): void
    {
        $alias = new Alias();
        $alias->setSource('alias@example.org');

        $invalidator = $this->createMock(EntityCacheInvalidator::class);
        $invalidator->expects(self::once())
            ->method('dispatch')
            ->with(EntityCacheType::ALIAS, 'alias@example.org');

        new EntityCacheInvalidationListener($invalidator)->onAliasChange($alias);
    }

    public function testOnDomainChangeDispatchesDomainType(): void
    {
        $domain = new Domain();
        $domain->setName('example.org');

        $invalidator = $this->createMock(EntityCacheInvalidator::class);
        $invalidator->expects(self::once())
            ->method('dispatch')
            ->with(EntityCacheType::DOMAIN, 'example.org');

        new EntityCacheInvalidationListener($invalidator)->onDomainChange($domain);
    }

    public function testOnOpenPgpKeyChangeDispatchesOpenPgpKeyType(): void
    {
        $openPgpKey = new OpenPgpKey();
        $openPgpKey->setEmail('user@example.org');

        $invalidator = $this->createMock(EntityCacheInvalidator::class);
        $invalidator->expects(self::once())
            ->method('dispatch')
            ->with(EntityCacheType::OPENPGP_KEY, 'user@example.org');

        new EntityCacheInvalidationListener($invalidator)->onOpenPgpKeyChange($openPgpKey);
    }
}
