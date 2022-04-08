<?php

namespace App\Command;

use App\Sender\WelcomeMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UsersRegistrationMailCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $manager;
    /**
     * @var WelcomeMessageSender
     */
    private $welcomeMessageSender;

    public function __construct(EntityManagerInterface $manager, WelcomeMessageSender $welcomeMessageSender, ?string $name = null)
    {
        parent::__construct($name);
        $this->manager = $manager;
        $this->welcomeMessageSender = $welcomeMessageSender;
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = $input->getOption('user');
        $locale = $input->getOption('locale');

        if (empty($email) || null === $user = $this->manager->getRepository('App:User')->findByEmail($email)) {
            throw new UsernameNotFoundException(sprintf('User with email %s not found!', $email));
        }

        $this->welcomeMessageSender->send($user, $locale);
    }
}
