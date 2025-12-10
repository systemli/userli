<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgKeyForUserException;
use App\Handler\WkdHandler;
use Override;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:openpgp:import-key', description: 'Import OpenPGP key for email')]
class OpenPgpImportKeyCommand extends Command
{
    public function __construct(private readonly WkdHandler $handler)
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
                'email address of the OpenPGP key')
            ->addArgument(
                'file',
                InputOption::VALUE_REQUIRED,
                'file to read the key from');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // parse arguments
        $email = $input->getArgument('email');
        $file = $input->getArgument('file');

        // Read contents from file
        if (!is_file($file)) {
            throw new RuntimeException('File not found: '.$file);
        }

        $content = file_get_contents($file);

        // Import the key
        try {
            $openPgpKey = $this->handler->importKey($content, $email);
        } catch (NoGpgKeyForUserException|MultipleGpgKeysForUserException $e) {
            $output->writeln(sprintf('Error: %s in %s', $e->getMessage(), $file));

            return 0;
        }

        $output->writeln(sprintf('Imported OpenPGP key for email %s: %s', $email, $openPgpKey->getKeyFingerprint()));

        return 0;
    }
}
