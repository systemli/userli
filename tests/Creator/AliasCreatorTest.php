<?php

declare(strict_types=1);

namespace App\Tests\Creator;

use App\Creator\AliasCreator;
use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AliasCreatorTest extends TestCase
{
    protected function createCreator(): AliasCreator
    {
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $manager->method('persist')->willReturnCallback(
            static function (Alias $alias): void {
                $alias->setId(1);
            }
        );
        $manager->method('flush')->willReturn(true);

        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        return new AliasCreator($manager, $validator, $eventDispatcher);
    }

    protected function createUser(): User
    {
        $domain = new Domain();
        $domain->setName('example.org');
        $user = new User('user@example.org');
        $user->setDomain($domain);

        return $user;
    }

    public function testCreate(): void
    {
        $creator = $this->createCreator();
        $user = $this->createUser();

        $alias = $creator->create($user, 'user');

        self::assertEquals('user@example.org', $alias->getSource());
    }

    public function testCreateRandom(): void
    {
        $creator = $this->createCreator();
        $user = $this->createUser();

        $alias = $creator->create($user, null);

        self::assertEquals(1, $alias->getId());
    }

    public function testCreateWithException(): void
    {
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();

        $violation = new ConstraintViolation('message', 'messageTemplate', [], null, null, 'someValue');

        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $validator->method('validate')->willReturn(new ConstraintViolationList([$violation]));

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $creator = new AliasCreator($manager, $validator, $eventDispatcher);

        $user = $this->createUser();

        $this->expectException(ValidationException::class);

        $creator->create($user, 'user');
    }
}
