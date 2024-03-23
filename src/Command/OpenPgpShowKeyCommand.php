<?php

namespace App\Command;

use App\Entity\OpenPgpKey;
use App\Repository\OpenPgpKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OpenPgpShowKeyCommand extends Command
{
    protected static $defaultName = 'app:openpgp:show-key';
    private readonly OpenPgpKeyRepository $repository;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->repository = $manager->getRepository(OpenPgpKey::class);
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Show OpenPGP key of email')
            ->addArgument(
                'email',
                InputOption::VALUE_REQUIRED,
                'email address of the OpenPGP key');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // parse arguments
        $email = $input->getArgument('email');

        // Check if OpenPGP key exists
        $openPgpKey = $this->repository->findByEmail($email);
        if (null === $openPgpKey) {
            $output->writeln(sprintf('No OpenPGP key found for email %s', $email));
        } else {
            $output->writeln(sprintf('OpenPGP key for email %s: %s', $openPgpKey->getEmail(), $openPgpKey->getKeyFingerprint()));
        }

        return 0;
    }
}
