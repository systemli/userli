<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\PasswordMismatchException;
use App\Exception\PasswordPolicyException;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'app:users:reset', description: 'Reset a user')]
final class UsersResetCommand extends AbstractUsersCommand
{
    public function __construct(
        EntityManagerInterface $manager,
        private readonly PasswordUpdater $passwordUpdater,
        private readonly MailCryptKeyHandler $mailCryptKeyHandler,
        private readonly RecoveryTokenHandler $recoveryTokenHandler,
    ) {
        parent::__construct($manager);
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->getUser($input, $output);
        if (null === $user) {
            return Command::FAILURE;
        }

        if ($user->isDeleted()) {
            $output->writeln(sprintf('<error>User with email %s is deleted! Consider to restore the user instead.</error>', $user->getEmail()));

            return Command::FAILURE;
        }

        $questionHelper = $this->getHelper('question');
        assert($questionHelper instanceof QuestionHelper);
        $confirmQuest = new ConfirmationQuestion('Really reset user? This will clear their mailbox: (yes|no) ', false);
        if (!$questionHelper->ask($input, $output, $confirmQuest)) {
            return Command::SUCCESS;
        }

        try {
            $password = $this->askForPassword($input, $output);
        } catch (PasswordPolicyException|PasswordMismatchException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        if ($input->getOption('dry-run')) {
            $output->write(sprintf("\nWould reset user %s\n\n", $user->getEmail()));

            return Command::SUCCESS;
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
        $user->setTotpBackupCodes([]);

        // Clear sensitive plaintext data from User object
        $user->eraseCredentials();

        $this->manager->flush();

        return Command::SUCCESS;
    }
}
