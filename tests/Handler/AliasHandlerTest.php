<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Creator\AliasCreator;
use App\Entity\Alias;
use App\Entity\User;
use App\Handler\AliasHandler;
use App\Repository\AliasRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AliasHandlerTest extends TestCase
{
    private function createHandler(array $list): AliasHandler
    {
        $repository = $this->createStub(AliasRepository::class);
        $repository->method('findByUser')->willReturn($list);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        $creator = $this->createStub(AliasCreator::class);

        return new AliasHandler($manager, $creator);
    }

    public function testCheckAliasLimit(): void
    {
        $handler = $this->createHandler([]);
        $user = new User('test@example.org');

        self::assertTrue($handler->checkAliasLimit([]));
    }

    public function testCreate(): void
    {
        $handler = $this->createHandler([]);
        $user = new User('test@example.org');

        self::assertInstanceOf(Alias::class, $handler->create($user, null));
    }

    public function testCreateLimit(): void
    {
        $list = [];
        for ($i = 0; $i <= AliasHandler::ALIAS_LIMIT_RANDOM; ++$i) {
            $list[] = 'dummy';
        }
        $handler = $this->createHandler($list);
        $user = new User('test@example.org');

        self::assertNull($handler->create($user, null));
    }
}
