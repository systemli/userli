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
     */
    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function updatePassword(User $user, string $plainPassword = null): void
    {
        if (null === $plainPassword) {
            $plainPassword = $user->getPlainPassword();
        }

        if (!$plainPassword) {
            return;
        }

        $encoder = $this->encoderFactory->getEncoder($user);
        $hash = $encoder->encodePassword($plainPassword, $user->getSalt());

        $user->setPassword($hash);

        $user->updateUpdatedTime();
    }
}
