<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\PasswordMismatchException;
use App\Exception\PasswordPolicyException;
use App\Handler\PasswordStrengthHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

final readonly class ConsolePasswordHelper
{
    public function __construct(
        private PasswordStrengthHandler $passwordStrengthHandler,
    ) {
    }

    /**
     * @throws PasswordPolicyException
     * @throws PasswordMismatchException
     */
    public function askForPassword(InputInterface $input, OutputInterface $output): string
    {
        $io = new SymfonyStyle($input, $output);

        $passwordQuest = new Question('New password: ');
        $passwordQuest->setValidator(function ($value) {
            if ($this->passwordStrengthHandler->validate($value)) {
                throw new PasswordPolicyException();
            }

            return $value;
        });
        $passwordQuest->setHidden(true);
        $passwordQuest->setHiddenFallback(false);
        $passwordQuest->setMaxAttempts(5);

        $password = $io->askQuestion($passwordQuest);

        $passwordConfirmQuest = new Question('Repeat password: ');
        $passwordConfirmQuest->setHidden(true);
        $passwordConfirmQuest->setHiddenFallback(false);

        $passwordConfirm = $io->askQuestion($passwordConfirmQuest);

        if ($password !== $passwordConfirm) {
            throw new PasswordMismatchException();
        }

        return $password;
    }
}
