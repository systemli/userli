<?php

namespace App\Command;

use App\Entity\ReservedName;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author doobry <doobry@systemli.org>
 */
class ImportReservedNamesCommand extends ContainerAwareCommand
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * VoucherUnlinkCommand constructor.
     *
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('usrmgmt:reservednames:import')
            ->setDescription('Import reservedNames from stdin or file')
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Simple text file with a list of reservedNames. Give "-" to read from STDIN.',
                dirname(__FILE__).'/../../config/reserved_names.txt');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->manager->getRepository('App:ReservedName');

        $file = $input->getOption('file');
        dump($file);
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
                        '<INFO>Adding reservedName "%s" to database table</INFO>',
                        $name
                    ),
                    OutputInterface::VERBOSITY_VERBOSE
                );

                $reservedName = new ReservedName();
                $reservedName->setName($name);

                $this->manager->persist($reservedName);
            } else {
                $output->writeln(
                    sprintf(
                        '<INFO>Skipping reservedName "%s", already exists</INFO>',
                        $name
                    ),
                    OutputInterface::VERBOSITY_VERY_VERBOSE
                );
            }
        }

        $this->manager->flush();
    }
}
