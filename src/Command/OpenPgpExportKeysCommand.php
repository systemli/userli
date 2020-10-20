<?php

namespace App\Command;

use App\Handler\WkdHandler;
use App\Repository\OpenPgpKeyRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OpenPgpExportKeysCommand extends Command
{
    /**
     * @var WkdHandler
     */
    private $handler;

    /**
     * @var OpenPgpKeyRepository
     */
    private $repository;

    public function __construct(ObjectManager $manager, WkdHandler $handler)
    {
        $this->handler = $handler;
        $this->repository = $manager->getRepository('App:OpenPgpKey');
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:wkd:export-keys')
            ->setDescription('Export all OpenPGP keys to WKD directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = 0;
        foreach ($this->repository->findAll() as $openPgpKey) {
            $this->handler->exportKeyToWKD($openPgpKey);
            ++$count;
        }

        $output->writeln(sprintf('Exported %d OpenPGP keys to WKD directory', $count));
    }
}
