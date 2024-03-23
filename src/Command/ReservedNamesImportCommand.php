<?php

namespace App\Command;

use App\Creator\ReservedNameCreator;
use App\Entity\ReservedName;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReservedNamesImportCommand extends Command
{
    protected static $defaultName = 'app:reservednames:import';
    public function __construct(private readonly EntityManagerInterface $manager, private readonly ReservedNameCreator $creator)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Import reserved names from stdin or file')
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Simple text file with a list of reserved names. Give "-" to read from STDIN.',
                dirname(__FILE__).'/../../config/reserved_names.txt'
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws ValidationException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repository = $this->manager->getRepository(ReservedName::class);

        $file = (string) $input->getOption('file');

        if ('-' === $file) {
            $handle = STDIN;
        } else {
            $handle = fopen($file, 'rb');
        }

        while ($line = fgets($handle)) {
            $name = trim($line);
            if (empty($name)) {
                continue;
            }

            if ('#' === $name[0]) {
                // filter out comments
                continue;
            }

            if (null === $repository->findByName($name)) {
                $output->writeln(
                    sprintf(
                        '<INFO>Adding reserved name "%s" to database table</INFO>',
                        $name
                    ),
                    OutputInterface::VERBOSITY_VERBOSE
                );

                $this->creator->create($name);
            } else {
                $output->writeln(
                    sprintf(
                        '<INFO>Skipping reserved name "%s", already exists</INFO>',
                        $name
                    ),
                    OutputInterface::VERBOSITY_VERY_VERBOSE
                );
            }
        }

        return 0;
    }
}
