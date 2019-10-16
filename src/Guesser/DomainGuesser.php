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
     *
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->repository = $manager->getRepository('App:Domain');
    }

    /**
     * @param string $email
     *
     * @return Domain|null
     */
    public function guess($email)
    {
        $splitted = explode('@', $email);

        return isset($splitted[1]) ? $this->repository->findByName($splitted[1]) : null;
    }
}
