<?php

namespace App\Command;

use App\Handler\UserRestoreHandler;
use Exception;
use App\Handler\PasswordStrengthHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsCommand(name: 'app:users:restore', description: 'Reset a user')]
class UsersRestoreCommand extends AbstractUsersCommand
{
    public function __construct(
        EntityManagerInterface $manager,
        private readonly UserRestoreHandler $userRestoreHandler,
    )
    {
        parent::__construct($manager);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->getUser($input);

        if (!$user->isDeleted()) {
            throw new UserNotFoundException(sprintf('User with email %s is still active! Consider to reset the user instead.', $user->getEmail()));
        }

        $questionHelper = $this->getHelper('question');

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
            $output->write(sprintf("\nWould restore user %s\n\n", $user->getEmail()));

            return 0;
        }

        $output->write(sprintf("\nRestoring user %s ...\n\n", $user->getEmail()));

        $recoveryToken = $this->userRestoreHandler->restoreUser($user, $password);
        if ($recoveryToken) {
            $output->write(sprintf("<info>New recovery token (please hand over to user): %s</info>\n\n", $recoveryToken));
        }

        return 0;
    }
}
