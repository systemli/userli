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
     * @param bool  $random
     *
     * @return bool
     */
    public function checkAliasLimit(array $aliases, bool $random = false): bool
    {
        $limit = ($random) ? self::ALIAS_LIMIT_RANDOM : self::ALIAS_LIMIT_CUSTOM;

        return (count($aliases) < $limit) ? true : false;
    }

    /**
     * @param User        $user
     * @param null|string $localPart
     *
     * @return Alias|null
     *
     * @throws ValidationException
     */
    public function create(User $user, ?string $localPart = null): ?Alias
    {
        if (isset($localPart)) {
            $random = false;
            $limit = self::ALIAS_LIMIT_CUSTOM;
        } else {
            $random = true;
            $limit = self::ALIAS_LIMIT_RANDOM;
        }

        $aliases = $this->repository->findByUser($user, $random);
        if ($this->checkAliasLimit($aliases, $limit)) {
            return $this->creator->create($user, $localPart);
        }

        return null;
    }
}
