<?php

namespace App\Handler;

use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

/**
 * Class UserAuthenticationHandler.
 */
class UserAuthenticationHandler
{
    /**
     * @var EncoderFactory
     */
    private $encoderFactory;

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * UserAuthenticationHandler constructor.
     *
     * @param ObjectManager  $manager
     * @param EncoderFactory $encoderFactory
     */
    public function __construct(ObjectManager $manager, EncoderFactory $encoderFactory)
    {
        $this->manager = $manager;
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * @param User $user
     */
    private function updateLastLogin(User $user)
    {
        $user->updateLastLoginTime();
        $this->manager->persist($user);
        $this->manager->flush();
    }

    /**
     * @param User   $user
     * @param string $password
     *
     * @return User|null
     */
    public function authenticate(User $user, string $password): ?User
    {
        $encoder = $this->encoderFactory->getEncoder($user);
        if (!$encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt())) {
            return null;
        }

        $this->updateLastLogin($user);

        return $user;
    }
}
