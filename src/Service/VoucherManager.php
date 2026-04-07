<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PaginatedResult;
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
    private const int PAGE_SIZE = 20;

    public function __construct(
        private EntityManagerInterface $em,
        private VoucherRepository $repository,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Find vouchers with offset-based pagination and optional filters.
     *
     * @return PaginatedResult<Voucher>
     */
    public function findPaginated(int $page = 1, string $search = '', ?Domain $domain = null, string $status = ''): PaginatedResult
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $total = $this->repository->countByFilters($search, $domain, $status);
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $items = $this->repository->findPaginatedByFilters($search, $domain, $status, self::PAGE_SIZE, $offset);

        return new PaginatedResult($items, $page, $totalPages, $total);
    }

    /**
     * Create a voucher for the admin interface (bypasses the per-user limit).
     *
     * @throws ValidationException
     */
    public function createForAdmin(string $code, User $user, Domain $domain): Voucher
    {
        $voucher = new Voucher($code);
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

    public function delete(Voucher $voucher): void
    {
        $this->em->remove($voucher);
        $this->em->flush();
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

        $domain = $user->getDomain();

        if (null === $domain || !$domain->getInvitationSettings()->isEnabled()) {
            return [];
        }

        $settings = $domain->getInvitationSettings();
        $limit = $settings->getLimit();
        $vouchers = $this->repository->findByUser($user);
        $waitingDays = $settings->getWaitingPeriodDays();

        if (null !== $user->getLastLoginTime() && count($vouchers) < $limit && $user->getCreationTime() <= new DateTimeImmutable(sprintf('-%d days', $waitingDays))) {
            for ($i = count($vouchers); $i < $limit; ++$i) {
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
