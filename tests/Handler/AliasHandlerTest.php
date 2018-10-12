<?php

namespace App\Tests\Handler;

use App\Creator\AliasCreator;
use App\Entity\Domain;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Handler\AliasHandler;
use App\Repository\AliasRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class AliasHandlerTest extends TestCase
{
    private function createHandler(array $list)
    {
        $repository = $this->getMockBuilder(AliasRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->any())->method('findByUser')->willReturn($list);

        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        $creator = $this->getMockBuilder(AliasCreator::class)->disableOriginalConstructor()->getMock();

        return new AliasHandler($manager, $creator);
    }


    public function testCheckAliasLimit()
    {
        $handler = $this->createHandler([]);
        $user = new User();

        self::assertTrue($handler->checkAliasLimit($user, []));
    }

    public function testCreate()
    {
        $handler = $this->createHandler([]);
        $user = new User();

        self::assertTrue($handler->create($user, [], null));
    }

    public function testCreateLimit()
    {
        $list = [];
        for ($i = 0; $i <= AliasHandler::ALIAS_LIMIT; ++$i) {
            $list[] = 'dummy';
        }
        $handler = $this->createHandler($list);
        $user = new User();

        self::assertFalse($handler->create($user, $list, null));
    }
}
