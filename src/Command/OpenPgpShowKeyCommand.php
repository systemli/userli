<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\OpenPgpKeyManager;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:openpgp:show-key', description: 'Show OpenPGP key of email')]
final readonly class OpenPgpShowKeyCommand
{
    public function __construct(private OpenPgpKeyManager $manager)
    {
    }

    public function __invoke(
        #[Argument(description: 'email address of the OpenPGP key')]
        string $email,
        OutputInterface $output,
    ): int {
        $openPgpKey = $this->manager->getKey($email);
        if (null === $openPgpKey) {
            $output->writeln(sprintf('No OpenPGP key found for email %s', $email));
        } else {
            $output->writeln(sprintf('OpenPGP key for email %s: %s', $openPgpKey->getEmail(), $openPgpKey->getKeyFingerprint()));
        }

        return Command::SUCCESS;
    }
}
