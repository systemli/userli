<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\PaginatedResult;
use App\Entity\Alias;
use App\Entity\User;
use App\Enum\Roles;
use App\Exception\ValidationException;
use App\Form\Model\AliasAdminModel;
use App\Guesser\DomainGuesser;
use App\Handler\DeleteHandler;
use App\Repository\AliasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class AliasManager
{
    private const int PAGE_SIZE = 20;

    public function __construct(
        private EntityManagerInterface $em,
        private AliasRepository $repository,
        private DomainGuesser $domainGuesser,
        private DeleteHandler $deleteHandler,
        private Security $security,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Find aliases with offset-based pagination and optional filters.
     *
     * @return PaginatedResult<Alias>
     */
    public function findPaginated(int $page = 1, string $search = '', string $deleted = 'active'): PaginatedResult
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $total = $this->repository->countByFilters($search, null, $deleted);
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $items = $this->repository->findPaginatedByFilters($search, null, $deleted, self::PAGE_SIZE, $offset);

        return new PaginatedResult($items, $page, $totalPages, $total);
    }

    /**
     * Create a new alias from the admin form model.
     *
     * @throws ValidationException
     */
    public function create(AliasAdminModel $model): Alias
    {
        $alias = new Alias();
        $alias->setSource($model->getSource());
        if (null !== $model->getUser()) {
            $alias->setUser($model->getUser());
        }

        $alias->setDestination($model->getDestination());
        $alias->setSmtpQuotaLimits($model->getSmtpQuotaLimits());

        $this->applyDefaults($alias);

        $violations = $this->validator->validate($alias);
        if ($violations->count() > 0) {
            throw new ValidationException($violations);
        }

        $this->em->persist($alias);
        $this->em->flush();

        return $alias;
    }

    /**
     * Update an existing alias from the admin form model.
     */
    public function update(Alias $alias, AliasAdminModel $model): void
    {
        if (null !== $model->getUser()) {
            $alias->setUser($model->getUser());
        }

        $alias->setDestination($model->getDestination());
        $alias->setSmtpQuotaLimits($model->getSmtpQuotaLimits());

        $this->applyDefaults($alias);

        $this->em->flush();
    }

    /**
     * Soft-delete an alias.
     */
    public function delete(Alias $alias): void
    {
        $this->deleteHandler->deleteAlias($alias);
    }

    /**
     * Apply default values for user, destination, and domain.
     *
     * Mirrors the prePersist/preUpdate logic from the Sonata AliasAdmin:
     * - If no destination and no user: set user to current user
     * - If user set but no destination: set destination to user's email
     * - Guess domain from source email
     * - Domain admins (non-ROLE_ADMIN) are forced to use the user's email as destination
     */
    private function applyDefaults(Alias $alias): void
    {
        if (null === $alias->getDestination() && null === $alias->getUser()) {
            $user = $this->security->getUser();
            if ($user instanceof User) {
                $alias->setUser($user);
            }
        }

        if (null === $alias->getDestination()) {
            $alias->setDestination($alias->getUser()?->getEmail());
        }

        if (null !== $alias->getSource()) {
            $domain = $this->domainGuesser->guess($alias->getSource());
            if (null !== $domain) {
                $alias->setDomain($domain);
            }
        }

        // Domain admins are only allowed to set alias destination to existing user's email
        if (!$this->security->isGranted(Roles::ADMIN)) {
            $alias->setDestination($alias->getUser()?->getEmail());
        }
    }
}
