<?php

declare(strict_types=1);

namespace App\Command;

use App\Handler\DeleteHandler;
use App\Repository\AliasRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:alias:delete', description: 'Delete an alias')]
final readonly class AliasDeleteCommand
{
    public function __construct(
        private UserRepository $userRepository,
        private AliasRepository $aliasRepository,
        private DeleteHandler $deleteHandler,
    ) {
    }

    public function __invoke(
        #[Option(name: 'alias', description: 'Alias address to delete', shortcut: 'a')]
        ?string $source = null,
        #[Option(name: 'user', description: 'User who owns the alias (optional)', shortcut: 'u')]
        ?string $email = null,
        #[Option(name: 'dry-run', description: 'Show what would be deleted without actually deleting')]
        bool $dryRun = false,
        ?OutputInterface $output = null,
    ): int {
        $user = null;
        if ($email && null === $user = $this->userRepository->findByEmail($email)) {
            $output->writeln(sprintf("<error>User with email '%s' not found!</error>", $email));

            return Command::FAILURE;
        }

        if (empty($source) || null === $alias = $this->aliasRepository->findOneBySource($source)) {
            $output->writeln(sprintf("<error>Alias with address '%s' not found!</error>", $source));

            return Command::FAILURE;
        }

        if ($dryRun) {
            if ($user !== null) {
                $output->write(sprintf("Would delete alias %s of user %s\n", $source, $email));
            } else {
                $output->write(sprintf("Would delete alias %s\n", $source));
            }
        } else {
            if ($user !== null) {
                $output->write(sprintf("Deleting alias %s of user %s\n", $source, $email));
                $this->deleteHandler->deleteAlias($alias, $user);
            } else {
                $output->write(sprintf("Deleting alias %s\n", $source));
                $this->deleteHandler->deleteAlias($alias);
            }
        }

        return Command::SUCCESS;
    }
}
