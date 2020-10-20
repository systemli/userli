<?php

namespace App\Command;

use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgKeyForUserException;
use App\Handler\WkdHandler;
use App\Repository\OpenPgpKeyRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OpenPgpImportKeyCommand extends Command
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
            ->setName('app:openpgp:import-key')
            ->setDescription('Import OpenPGP key for email')
            ->addArgument(
                'email',
                InputOption::VALUE_REQUIRED,
                'email address of the OpenPGP key')
            ->addArgument(
                'file',
                InputOption::VALUE_REQUIRED,
                'file to read the key from');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // parse arguments
        $email = $input->getArgument('email');
        $file = $input->getArgument('file');

        // Read contents from file
        if (!is_file($file)) {
            throw new \RuntimeException('File not found: '.$file);
        }
        $content = file_get_contents($file);

        // Import the key
        try {
            $openPgpKey = $this->handler->importKey($content, $email);
        } catch (NoGpgKeyForUserException | MultipleGpgKeysForUserException $e) {
            $output->writeln(sprintf('Error: %s in %s', $e->getMessage(), $file));

            return;
        }

        $output->writeln(sprintf('Imported OpenPGP key for email %s: %s', $email, $openPgpKey->getKeyFingerprint()));
    }
}
