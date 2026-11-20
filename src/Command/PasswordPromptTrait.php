<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\PasswordMismatchException;
use App\Exception\PasswordPolicyException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Asks for a new password twice on the console.
 *
 * Only use in classes that extend Symfony\Component\Console\Command\Command.
 */
trait PasswordPromptTrait
{
    /**
     * @throws PasswordPolicyException
     * @throws PasswordMismatchException
     */
    protected function askForPassword(InputInterface $input, OutputInterface $output): string
    {
        $questionHelper = $this->getHelper('question');
        assert($questionHelper instanceof QuestionHelper);

        $passwordQuest = new Question('New password: ');
        $passwordQuest->setValidator(static function ($value) {
            if (strlen($value) < 12) {
                throw new PasswordPolicyException();
            }

            return $value;
        });
        $passwordQuest->setHidden(true);
        $passwordQuest->setHiddenFallback(false);
        $passwordQuest->setMaxAttempts(5);

        $password = $questionHelper->ask($input, $output, $passwordQuest);

        $passwordConfirmQuest = new Question('Repeat password: ');
        $passwordConfirmQuest->setHidden(true);
        $passwordConfirmQuest->setHiddenFallback(false);

        $passwordConfirm = $questionHelper->ask($input, $output, $passwordConfirmQuest);

        if ($password !== $passwordConfirm) {
            throw new PasswordMismatchException();
        }

        return $password;
    }
}
