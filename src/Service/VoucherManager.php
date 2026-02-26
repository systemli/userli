<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Exception\ValidationException;
use App\Helper\RandomStringGenerator;
use App\Repository\VoucherRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class VoucherManager
{
    private const int VOUCHER_LIMIT = 3;

    public function __construct(
        private EntityManagerInterface $em,
        private VoucherRepository $repository,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function create(User $user, Domain $domain): Voucher
    {
        $this->assertDomainPermission($user, $domain);

        $voucher = new Voucher(RandomStringGenerator::generate(6, true));
        $voucher->setUser($user);
        $voucher->setDomain($domain);

        $violations = $this->validator->validate($voucher);
        if ($violations->count() > 0) {
            throw new ValidationException($violations);
        }

        $this->em->persist($voucher);
        $this->em->flush();

        return $voucher;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function assertDomainPermission(User $user, Domain $domain): void
    {
        if ($user->hasRole(Roles::ADMIN)) {
            return;
        }

        if ($user->getDomain() !== $domain) {
            throw new InvalidArgumentException('User is not authorized to create vouchers for this domain');
        }
    }

    /**
     * @return Voucher[]
     */
    public function getVouchersByUser(User $user, bool $redeemed = false): array
    {
        if ($user->hasRole(Roles::SUSPICIOUS)) {
            return [];
        }

        $vouchers = $this->repository->findByUser($user);

        if (null !== $user->getLastLoginTime() && count($vouchers) < self::VOUCHER_LIMIT && $user->getCreationTime() <= new DateTimeImmutable('-7 days')) {
            for ($i = count($vouchers); $i < self::VOUCHER_LIMIT; ++$i) {
                try {
                    $vouchers[] = $this->create($user, $user->getDomain());
                } catch (ValidationException) {
                    // Should not throw
                }
            }
        }

        if (true === $redeemed) {
            return $vouchers;
        }

        return array_filter($vouchers, static fn (Voucher $voucher) => ($voucher->isRedeemed()) ? null : $voucher);
    }
}
