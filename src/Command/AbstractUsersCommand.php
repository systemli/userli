<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Exception\PasswordMismatchException;
use App\Exception\PasswordPolicyException;
use App\Handler\PasswordStrengthHandler;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

abstract class AbstractUsersCommand extends Command
{
    public function __construct(
        protected readonly EntityManagerInterface $manager,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User to act upon')
            ->addOption('dry-run', null, InputOption::VALUE_NONE);
    }

    protected function getUser(InputInterface $input, OutputInterface $output): ?User
    {
        $email = $input->getOption('user');
        if (empty($email) || null === $user = $this->manager->getRepository(User::class)->findByEmail($email)) {
            $output->writeln(sprintf('<error>User with email %s not found!</error>', $email));

            return null;
        }

        return $user;
    }

    /**
     * @throws PasswordPolicyException
     * @throws PasswordMismatchException
     */
    protected function askForPassword(InputInterface $input, OutputInterface $output): string
    {
        $questionHelper = $this->getHelper('question');
        assert($questionHelper instanceof QuestionHelper);

        $passwordQuest = new Question('New password: ');
        $passwordQuest->setValidator(function ($value) {
            $validator = new PasswordStrengthHandler();
            if ($validator->validate($value)) {
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
