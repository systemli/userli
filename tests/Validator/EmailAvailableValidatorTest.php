<?php

declare(strict_types=1);

namespace App\Tests\Validator;

use App\Entity\Alias;
use App\Entity\ReservedName;
use App\Entity\User;
use App\Repository\AliasRepository;
use App\Repository\ReservedNameRepository;
use App\Repository\UserRepository;
use App\Validator\EmailAvailable;
use App\Validator\EmailAvailableValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmailAvailableValidatorTest extends ConstraintValidatorTestCase
{
    private $aliasUsed = 'alias@example.org';
    private $userUsed = 'user@example.org';

    protected function createValidator(): EmailAvailableValidator
    {
        $aliasRepository = $this->createStub(AliasRepository::class);
        $aliasRepository->method('findOneBySource')->willReturnMap([
            [$this->aliasUsed, true, new Alias()],
        ]);
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findOneBy')->willReturnMap([
            [['email' => $this->userUsed], null, new User($this->userUsed)],
        ]);
        $reservedNameRepository = $this->createStub(ReservedNameRepository::class);
        $reservedNameRepository->method('findByName')->willReturnMap([
            ['reserved', new ReservedName()],
        ]);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnMap([
            [Alias::class, $aliasRepository],
            [ReservedName::class, $reservedNameRepository],
            [User::class, $userRepository],
        ]);

        return new EmailAvailableValidator($manager);
    }

    public function testExpectsEmailAvailableType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('string', new Valid());
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new EmailAvailable());

        self::assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new EmailAvailable());

        self::assertNoViolation();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new stdClass(), new EmailAvailable());
    }

    public function testNewAddressIsValid(): void
    {
        $this->validator->validate('new@example.org', new EmailAvailable());
        self::assertNoViolation();
    }

    #[DataProvider('getTakenAddresses')]
    public function testTakenAddressRaisesViolation(string $address): void
    {
        $this->validator->validate($address, new EmailAvailable());
        $this->buildViolation('registration.email-already-taken')
            ->assertRaised();
    }

    public static function getTakenAddresses(): array
    {
        return [
            ['user@example.org'],
            ['alias@example.org'],
            ['reserved@example.org'],
        ];
    }
}
