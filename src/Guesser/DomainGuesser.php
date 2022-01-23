<?php

namespace App\Guesser;

use App\Entity\Domain;
use App\Repository\DomainRepository;
use Doctrine\Common\Persistence\ObjectManager;

class DomainGuesser
{
    /**
     * @var DomainRepository
     */
    private $repository;

    /**
     * Constructor.
     */
    public function __construct(ObjectManager $manager)
    {
        $this->repository = $manager->getRepository('App:Domain');
    }

    public function guess(string $email): ?Domain
    {
        $splitted = explode('@', $email);

        return isset($splitted[1]) ? $this->repository->findByName($splitted[1]) : null;
    }
}
