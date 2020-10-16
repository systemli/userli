<?php

namespace App\Command;

use App\Handler\WkdHandler;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WkdExportKeysCommand extends Command
{
    /**
     * @var WkdHandler
     */
    private $handler;

    /**
     * @var UserRepository
     */
    private $repository;

    public function __construct(ObjectManager $manager, WkdHandler $handler)
    {
        $this->handler = $handler;
        $this->repository = $manager->getRepository('App:User');
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:users:wkd:export-keys')
            ->setDescription('Export all WKD keys to WKD directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = 0;
        foreach ($this->repository->findUsersWithWkdKey() as $user) {
            $this->handler->exportKeyToWKD($user);
            ++$count;
        }

        $output->writeln(sprintf('Exported %d WKD keys to WKD directory', $count));
    }
}
