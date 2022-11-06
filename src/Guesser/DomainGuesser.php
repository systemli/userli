<?php

namespace App\Guesser;

use App\Entity\Domain;
use App\Repository\DomainRepository;
use Doctrine\ORM\EntityManagerInterface;

class DomainGuesser
{
    private DomainRepository $repository;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->repository = $manager->getRepository(Domain::class);
    }

    public function guess(string $email): ?Domain
    {
        $splitted = explode('@', $email);

        return isset($splitted[1]) ? $this->repository->findByName($splitted[1]) : null;
    }
}
