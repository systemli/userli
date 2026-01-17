<?php

declare(strict_types=1);

namespace App\Command;

use App\Handler\MailCryptKeyHandler;
use App\Handler\PasswordStrengthHandler;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsCommand(name: 'app:users:reset', description: 'Reset a user')]
final class UsersResetCommand extends AbstractUsersCommand
{
    /**
     * RegistrationMailCommand constructor.
     */
    public function __construct(
        EntityManagerInterface $manager,
        private readonly PasswordUpdater $passwordUpdater,
        private readonly MailCryptKeyHandler $mailCryptKeyHandler,
        private readonly RecoveryTokenHandler $recoveryTokenHandler,
    ) {
        parent::__construct($manager);
    }

    #[Override]
    protected function configure(): void
    {
        parent::configure();
    }

    /**
     * @throws Exception
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->getUser($input);

        if ($user->isDeleted()) {
            throw new UserNotFoundException(sprintf('User with email %s is deleted! Consider to restore the user instead.', $user->getEmail()));
        }

        $questionHelper = $this->getHelper('question');
        assert($questionHelper instanceof QuestionHelper);
        $confirmQuest = new ConfirmationQuestion('Really reset user? This will clear their mailbox: (yes|no) ', false);
        if (!$questionHelper->ask($input, $output, $confirmQuest)) {
            return 0;
        }

        $passwordQuest = new Question('New password: ');
        $passwordQuest->setValidator(function ($value) {
            $validator = new PasswordStrengthHandler();
            if ($validator->validate($value)) {
                throw new Exception("The password doesn't comply with our security policy.");
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
            throw new Exception("The passwords don't match");
        }

        if ($input->getOption('dry-run')) {
            $output->write(sprintf("\nWould reset user %s\n\n", $user->getEmail()));

            return 0;
        }

        $output->write(sprintf("\nResetting user %s ...\n\n", $user->getEmail()));

        $this->passwordUpdater->updatePassword($user, $password);

        // Generate MailCrypt key with new password (overwrites old MailCrypt key)
        if ($user->hasMailCryptSecretBox()) {
            $this->mailCryptKeyHandler->create($user, $password);

            // Reset recovery token
            $this->recoveryTokenHandler->create($user);
            $output->write(sprintf("<info>New recovery token (please hand over to user): %s</info>\n\n", $user->getPlainRecoveryToken()));
        }

        // Reset twofactor settings
        $user->setTotpConfirmed(false);
        $user->setTotpSecret(null);
        $user->clearBackupCodes();

        // Clear sensitive plaintext data from User object
        $user->eraseCredentials();

        $this->manager->flush();

        return 0;
    }
}
