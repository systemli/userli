<?php

namespace App\Helper;

use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * Class PasswordUpdater.
 */
class PasswordUpdater
{
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * PasswordUpdater constructor.
     *
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * @param User $user
     */
    public function updatePassword(User $user)
    {
        $plainPassword = $user->getPlainPassword();

        if (0 === strlen($plainPassword)) {
            return;
        }

        $encoder = $this->encoderFactory->getEncoder($user);
        $hash = $encoder->encodePassword($plainPassword, $user->getSalt());

        $user->setPassword($hash);
        $user->updateUpdatedTime();
    }
}
