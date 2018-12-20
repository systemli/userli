<?php

namespace App\Helper;

use App\Entity\User;
use App\Handler\RecoveryTokenHandler;
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
     * @var RecoveryTokenHandler
     */
    private $recoveryTokenHandler;

    /**
     * PasswordUpdater constructor.
     *
     * @param EncoderFactoryInterface $encoderFactory
     * @param RecoveryTokenHandler $recoveryTokenHandler
     */
    public function __construct(EncoderFactoryInterface $encoderFactory, RecoveryTokenHandler $recoveryTokenHandler)
    {
        $this->encoderFactory = $encoderFactory;
        $this->recoveryTokenHandler = $recoveryTokenHandler;
    }

    /**
     * @param User        $user
     * @param string|null $plainPassword
     */
    public function updatePassword(User $user, string $plainPassword = null)
    {
        if (null === $plainPassword) {
            $plainPassword = $user->getPlainPassword();
        }

        if (0 === strlen($plainPassword)) {
            return;
        }

        $encoder = $this->encoderFactory->getEncoder($user);
        $hash = $encoder->encodePassword($plainPassword, $user->getSalt());

        $user->setPassword($hash);

        // Update recovery token only if set
        if ($user->hasRecoveryToken())
        {
            $this->recoveryTokenHandler->update($user);
        }

        $user->updateUpdatedTime();
    }
}
