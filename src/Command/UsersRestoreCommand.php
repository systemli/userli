<?php

namespace App\Command;

use App\Enum\MailCrypt;
use Exception;
use App\Entity\User;
use App\Handler\MailCryptKeyHandler;
use App\Handler\PasswordStrengthHandler;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsCommand(name: 'app:users:restore')]
class UsersRestoreCommand extends Command
{
    private readonly MailCrypt $mailCrypt;

    public function __construct(private readonly EntityManagerInterface $manager,
                                private readonly PasswordUpdater $passwordUpdater,
                                private readonly MailCryptKeyHandler $mailCryptKeyHandler,
                                private readonly RecoveryTokenHandler $recoveryTokenHandler,
                                private readonly int $mailCryptEnv)
    {
        parent::__construct();
        $this->mailCrypt = MailCrypt::from($this->mailCryptEnv);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Reset a user')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Deleted user to restore')
            ->addOption('dry-run', null, InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getOption('user');

        if (empty($email)
            || null === $user = $this->manager->getRepository(User::class)->findByEmail($email)) {
            throw new UserNotFoundException(sprintf('User with email %s not found!', $email));
        }

        if (!$user->isDeleted()) {
            throw new UserNotFoundException(sprintf('User with email %s is still active! Consider to reset the user instead.', $email));
        }

        $questionHelper = $this->getHelper('question');

        $passwordQuest = new Question('New password: ');
        $passwordQuest->setValidator(function ($value) {
            $validator = new PasswordStrengthHandler();
            if ($validator->validate($value)) {
                throw new Exception('The password doesn\'t comply with our security policy.');
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
            throw new Exception('The passwords don\'t match');
        }

        if ($input->getOption('dry-run')) {
            $output->write(sprintf("\nWould restore user %s\n\n", $email));

            return 0;
        }

        $output->write(sprintf("\nRestoring user %s ...\n\n", $email));

        $user->setDeleted(false);
        $this->passwordUpdater->updatePassword($user, $password);

        // Generate MailCrypt key with new password (overwrites old MailCrypt key)
        if ($this->mailCrypt->isAtLeast(MailCrypt::ENABLED_ENFORCE_NEW_USERS)) {
            $this->mailCryptKeyHandler->create($user, $password);
            $user->setMailCryptEnabled(true);

            // Reset recovery token
            $this->recoveryTokenHandler->create($user);
            $output->write(sprintf("<info>New recovery token (please hand over to user): %s</info>\n\n", $user->getPlainRecoveryToken()));
        }

        // Clear sensitive plaintext data from User object
        $user->eraseCredentials();

        $this->manager->flush();

        return 0;
    }
}
