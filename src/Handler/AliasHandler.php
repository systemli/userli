<?php

namespace App\Handler;

use App\Creator\AliasCreator;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Repository\AliasRepository;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AliasHandler.
 */
class AliasHandler
{
    const ALIAS_LIMIT = 20;

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
     * @param ObjectManager  $manager
     * @param AliasCreator $creator
     */
    public function __construct(ObjectManager $manager, AliasCreator $creator)
    {
        $this->repository = $manager->getRepository('App:Alias');
        $this->creator = $creator;
    }

    /**
     * @param User $user
     * @param array $aliases
     * @return bool
     */
    public function checkAliasLimit(User $user, array $aliases): bool
    {
        return (count($aliases) < self::ALIAS_LIMIT) ? true : false;
    }

    /**
     * @param User $user
     * @param array $aliases
     * @param null|string $localPart
     * @return bool
     * @throws ValidationException
     */
    public function create(User $user, array $aliases, ?string $localPart): bool
    {
        if ($this->checkAliasLimit($user, $aliases)) {
            $this->creator->create($user, $localPart);
            return true;
        } else {
            return false;
        }
    }
}
