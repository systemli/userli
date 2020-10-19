<?php

namespace App\Command;

use App\Handler\WkdHandler;
use App\Repository\OpenPgpKeyRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OpenPgpDeleteKeyCommand extends Command
{
    /**
     * @var WkdHandler
     */
    private $handler;

    /**
     * @var OpenPgpKeyRepository
     */
    private $repository;

    public function __construct(ObjectManager $manager, WkdHandler $handler)
    {
        $this->handler = $handler;
        $this->repository = $manager->getRepository('App:OpenPgpKey');
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:openpgp:delete-key')
            ->setDescription('Delete OpenPGP key for email')
            ->addArgument(
                'email',
                InputOption::VALUE_REQUIRED,
                'email address of the OpenPGP key');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // parse arguments
        $email = $input->getArgument('email');

        // Check if OpenPGP key exists
        $openPgpKey = $this->repository->findByEmail($email);
        if (null === $openPgpKey) {
            $output->writeln(sprintf('No OpenPGP key found for email %s', $email));
        } else {
            // Delete the key
            $this->handler->deleteKey($openPgpKey->getEmail());
            $output->writeln(sprintf('Deleted OpenPGP key for email %s: %s', $openPgpKey->getEmail(), $openPgpKey->getKeyFingerprint()));
        }
    }
}
