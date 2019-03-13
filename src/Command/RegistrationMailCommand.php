<?php

namespace App\Command;

use App\Sender\WelcomeMessageSender;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * @author louis <louis@systemli.org>
 */
class RegistrationMailCommand extends Command
{
    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var WelcomeMessageSender
     */
    private $welcomeMessageSender;

    /**
     * RegistrationMailCommand constructor.
     * @param ObjectManager $manager
     * @param WelcomeMessageSender $welcomeMessageSender
     * @param string|null $name
     */
    public function __construct(ObjectManager $manager, WelcomeMessageSender $welcomeMessageSender, ?string $name = null)
    {
        parent::__construct($name);
        $this->manager = $manager;
        $this->welcomeMessageSender = $welcomeMessageSender;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:registration:mail')
            ->setDescription('Send a registration mail to a user')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User who get the voucher(s)')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'the locale', 'de');
    }

    /**
     * {@inheritdoc}
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
