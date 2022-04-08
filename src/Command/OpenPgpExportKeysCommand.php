<?php

namespace App\Command;

use App\Handler\WkdHandler;
use App\Repository\DomainRepository;
use App\Repository\OpenPgpKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
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
     * @var DomainRepository
     */
    private $domainRepository;

    /**
     * @var OpenPgpKeyRepository
     */
    private $openPgpKeyRepository;

    public function __construct(EntityManagerInterface $manager, WkdHandler $handler)
    {
        $this->handler = $handler;
        $this->domainRepository = $manager->getRepository('App:Domain');
        $this->openPgpKeyRepository = $manager->getRepository('App:OpenPgpKey');
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:wkd:export-keys')
            ->setDescription('Export all OpenPGP keys to WKD directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        // create Web Key Directories (WKD) for all domains
        foreach ($this->domainRepository->findAll() as $domain) {
            $this->handler->getDomainWkdPath($domain->getName());
        }

        // export all OpenPGP keys to Web Key Directory (WKD)
        $count = 0;
        foreach ($this->openPgpKeyRepository->findAll() as $openPgpKey) {
            $this->handler->exportKeyToWKD($openPgpKey);
            ++$count;
        }

        $output->writeln(sprintf('Exported %d OpenPGP keys to WKD directory', $count));
    }
}
