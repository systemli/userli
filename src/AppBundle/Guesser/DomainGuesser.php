<?php

namespace AppBundle\Guesser;

use AppBundle\Entity\Domain;
use AppBundle\Repository\DomainRepository;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * @author louis <louis@systemli.org>
 */
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
        $this->repository = $manager->getRepository('AppBundle:Domain');
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
