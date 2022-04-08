<?php

namespace App\Handler;

use App\Entity\User;
use App\Event\LoginEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
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
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * UserAuthenticationHandler constructor.
     */
    public function __construct(EncoderFactory $encoderFactory, EventDispatcherInterface $eventDispatcher)
    {
        $this->encoderFactory = $encoderFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function authenticate(User $user, string $password): ?User
    {
        $encoder = $this->encoderFactory->getEncoder($user);
        if (!$encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt())) {
            return null;
        }
        $user->setPlainPassword($password);

        $this->eventDispatcher->dispatch(new LoginEvent($user), LoginEvent::NAME);

        return $user;
    }
}
