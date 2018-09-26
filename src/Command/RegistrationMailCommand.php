<?php

namespace App\Command;

use App\Sender\WelcomeMessageSender;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * @author louis <louis@systemli.org>
 */
class RegistrationMailCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('usrmgmt:registration:mail')
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

        if (empty($email) || null === $user = $this->getUserRepository()->findByEmail($email)) {
            throw new UsernameNotFoundException(sprintf('User with email %s not found!', $email));
        }

        $this->getWelcomeMessageSender()->send($user, $locale);
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('App:User');
    }

    /**
     * @return WelcomeMessageSender
     */
    private function getWelcomeMessageSender()
    {
        return $this->getContainer()->get(WelcomeMessageSender::class);
    }
}
