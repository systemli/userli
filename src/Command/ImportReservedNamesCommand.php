<?php

namespace App\Command;

use App\Entity\ReservedName;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

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
            ->setDescription('Import reservedNames from a yaml file')
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Yaml file with list of reservedNames',
                dirname(__FILE__).'/../../config/reserved_names.yml');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->manager->getRepository('App:ReservedName');

        $file = $input->getOption('file');
        $reservedNames = Yaml::parsefile($file);

        foreach ($reservedNames['reservedNames'] as $name) {
            if (null === $repository->findByName($name)) {
                $output->writeln(
                    sprintf(
                        '<INFO>Adding reservedName %s to database table</INFO>',
                        $name
                    ),
                    OutputInterface::VERBOSITY_VERBOSE
                );
                $reservedName = new ReservedName();
                $reservedName->setName($name);

                $this->manager->persist($reservedName);
            }
        }
        $this->manager->flush();
    }
}
