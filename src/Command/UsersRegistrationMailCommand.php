<?php

namespace App\Command;

use Exception;
use App\Entity\User;
use App\Sender\WelcomeMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsCommand(name: 'app:users:registration:mail')]
class UsersRegistrationMailCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly WelcomeMessageSender $welcomeMessageSender,
        private readonly string $defaultLocale,
        ?string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Send a registration mail to a user')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User who get the voucher(s)')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'the locale', $this->defaultLocale);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getOption('user');
        $locale = $input->getOption('locale');

        if (empty($email) || null === $user = $this->manager->getRepository(User::class)->findByEmail($email)) {
            throw new UserNotFoundException(sprintf('User with email %s not found!', $email));
        }

        $this->welcomeMessageSender->send($user, $locale);

        return 0;
    }
}
