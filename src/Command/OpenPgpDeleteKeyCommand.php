<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\OpenPgpKeyManager;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:openpgp:delete-key', description: 'Delete OpenPGP key for email')]
final class OpenPgpDeleteKeyCommand extends Command
{
    public function __construct(private readonly OpenPgpKeyManager $manager)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addArgument(
                'email',
                InputOption::VALUE_REQUIRED,
                'email address of the OpenPGP key');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // parse arguments
        $email = $input->getArgument('email');

        // Check if OpenPGP key exists
        $openPgpKey = $this->manager->getKey($email);
        if (null === $openPgpKey) {
            $output->writeln(sprintf('No OpenPGP key found for email %s', $email));
        } else {
            // Delete the key
            $this->manager->deleteKey($openPgpKey->getEmail());
            $output->writeln(sprintf('Deleted OpenPGP key for email %s: %s', $openPgpKey->getEmail(), $openPgpKey->getKeyFingerprint()));
        }

        return Command::SUCCESS;
    }
}
