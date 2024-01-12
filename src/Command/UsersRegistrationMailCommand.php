<?php

namespace App\Command;

use App\Entity\User;
use App\Sender\WelcomeMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class UsersRegistrationMailCommand extends Command
{
    public function __construct(private EntityManagerInterface $manager, private WelcomeMessageSender $welcomeMessageSender, ?string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:users:registration:mail')
            ->setDescription('Send a registration mail to a user')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User who get the voucher(s)')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'the locale', 'de');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
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
