<?php

namespace App\Command;

use App\Creator\ReservedNameCreator;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author doobry <doobry@systemli.org>
 */
class ImportReservedNamesCommand extends Command
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * ImportReservedNamesCommand constructor.
     * @param ObjectManager $manager
     * @param ValidatorInterface $validator
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ObjectManager $manager, ValidatorInterface $validator, EventDispatcherInterface $eventDispatcher)
    {
        $this->manager = $manager;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
        parent::__construct();
    }
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('usrmgmt:reservednames:import')
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
     * @throws \App\Exception\ValidationException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->manager->getRepository('App:ReservedName');

        $file = $input->getOption('file');
        if ('-' === $file) {
            $handle = STDIN;
        } else {
            $handle = fopen($file, 'r');
        }

        while ($line = fgets($handle)) {
            $name = trim($line);
            if (empty($name)) {
                continue;
            } elseif (substr($name, 0, 1) === "#") {
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

                $reservedNameCreator = new ReservedNameCreator($this->manager, $this->validator, $this->eventDispatcher);
                $reservedNameCreator->create($name);
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
    }
}
