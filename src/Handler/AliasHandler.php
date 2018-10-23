<?php

namespace App\Handler;

use App\Creator\AliasCreator;
use App\Entity\Alias;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Repository\AliasRepository;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AliasHandler.
 */
class AliasHandler
{
    const ALIAS_LIMIT_CUSTOM = 10;
    const ALIAS_LIMIT_RANDOM = 100;

    /**
     * @var AliasRepository
     */
    private $repository;
    /**
     * @var AliasCreator
     */
    private $creator;

    /**
     * AliasHandler constructor.
     *
     * @param ObjectManager $manager
     * @param AliasCreator  $creator
     */
    public function __construct(ObjectManager $manager, AliasCreator $creator)
    {
        $this->repository = $manager->getRepository('App:Alias');
        $this->creator = $creator;
    }

    /**
     * @param array $aliases
     * @param int $limit
     * @return bool
     */
    public function checkAliasLimit(array $aliases, int $limit = self::ALIAS_LIMIT_CUSTOM): bool
    {
        return (count($aliases) < $limit) ? true : false;
    }

    /**
     * @param User $user
     * @return Alias|null
     *
     * @throws ValidationException
     */
    public function createRandom(User $user): ?Alias
    {
        $aliases = $this->repository->findRandomByUser($user);
        if ($this->checkAliasLimit($aliases, self::ALIAS_LIMIT_RANDOM)) {
            return $this->creator->create($user, null);
        }

        return null;
    }

    /**
     * @param User $user
     * @param string $localPart
     * @return Alias|null
     * @throws ValidationException
     */
    public function createCustom(User $user, string $localPart): ?Alias
    {
        $aliases = $this->repository->findCustomByUser($user);
        if ($this->checkAliasLimit($aliases, self::ALIAS_LIMIT_CUSTOM)) {
            return $this->creator->create($user, $localPart);
        }

        return null;
    }
}
