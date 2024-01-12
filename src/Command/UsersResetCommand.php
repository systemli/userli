<?php

namespace App\Command;

use Exception;
use App\Entity\User;
use App\Handler\MailCryptKeyHandler;
use App\Handler\PasswordStrengthHandler;
use App\Handler\RecoveryTokenHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class UsersResetCommand extends Command
{
    protected static $defaultName = 'app:users:reset';
    /**
     * RegistrationMailCommand constructor.
     */
    public function __construct(private EntityManagerInterface $manager,
                                private PasswordUpdater $passwordUpdater,
                                private MailCryptKeyHandler $mailCryptKeyHandler,
                                private RecoveryTokenHandler $recoveryTokenHandler,
                                private string $mailLocation)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Reset a user')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User to reset')
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

        if (empty($email) || null === $user = $this->manager->getRepository(User::class)->findByEmail($email)) {
            throw new UserNotFoundException(sprintf('User with email %s not found!', $email));
        }

        $questionHelper = $this->getHelper('question');
        $confirmQuest = new ConfirmationQuestion('Really reset user? This will clear their mailbox: (yes|no) ', false);
        if (!$questionHelper->ask($input, $output, $confirmQuest)) {
            return 0;
        }

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
            $output->write(sprintf("\nWould reset user %s\n\n", $email));

            return 0;
        }

        $output->write(sprintf("\nResetting user %s ...\n\n", $email));

        // Set new password
        $user->setPlainPassword($password);
        $this->passwordUpdater->updatePassword($user);

        // Generate MailCrypt key with new password (overwrites old MailCrypt key)
        if ($user->hasMailCryptSecretBox()) {
            $this->mailCryptKeyHandler->create($user);

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

        // Clear users mailbox
        [$localPart, $domain] = explode('@', $email);
        $path = $this->mailLocation.DIRECTORY_SEPARATOR.$domain.DIRECTORY_SEPARATOR.$localPart.DIRECTORY_SEPARATOR.'Maildir';
        $filesystem = new Filesystem();
        if ($filesystem->exists($path)) {
            $output->writeln(sprintf('Delete directory for user: %s', $email));

            try {
                $filesystem->remove($path);
            } catch (IOException $e) {
                $output->writeln('<error>'.$e->getMessage().'</error>');
                $output->writeln(sprintf("<comment>Please manually clear the mailbox at '%s'</comment>", $path));

                return 1;
            }
        } else {
            $output->writeln(sprintf("<error>Error:</error> Directory for user '%s' not found (e.g. due to missing permissions).", $email));
            $output->writeln(sprintf("<comment>Please manually clear the mailbox at '%s'</comment>", $path));

            return 1;
        }

        return 0;
    }
}
