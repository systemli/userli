<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\AliasConversionException;
use App\Exception\PasswordMismatchException;
use App\Exception\PasswordPolicyException;
use App\Repository\AliasRepository;
use App\Service\UserManager;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:alias:convert-to-user', description: 'Convert an alias into a new user')]
final class AliasConvertToUserCommand extends Command
{
    use PasswordPromptTrait;

    public function __construct(
        private readonly AliasRepository $aliasRepository,
        private readonly UserManager $userManager,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addOption('alias', 'a', InputOption::VALUE_REQUIRED, 'Alias address to convert')
            ->addOption('dry-run', null, InputOption::VALUE_NONE);
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = (string) $input->getOption('alias');

        if ('' === $source || null === $alias = $this->aliasRepository->findOneBySource($source)) {
            $output->writeln(sprintf("<error>Alias with address '%s' not found!</error>", $source));

            return Command::FAILURE;
        }

        try {
            $this->userManager->assertConvertible($alias);
        } catch (AliasConversionException $aliasConversionException) {
            $output->writeln(sprintf('<error>%s</error>', $aliasConversionException->getMessage()));

            return Command::FAILURE;
        }

        if ($input->getOption('dry-run')) {
            $output->writeln(sprintf('Would delete alias %s and create a new user %s', $source, $source));

            return Command::SUCCESS;
        }

        try {
            $password = $this->askForPassword($input, $output);
        } catch (PasswordPolicyException|PasswordMismatchException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        try {
            $this->userManager->createFromAlias($alias, $password);
        } catch (AliasConversionException $aliasConversionException) {
            $output->writeln(sprintf('<error>%s</error>', $aliasConversionException->getMessage()));

            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Converted alias %s into a new user.</info>', $source));
        $output->writeln('The user has to change the password at the first login.');

        return Command::SUCCESS;
    }
}
