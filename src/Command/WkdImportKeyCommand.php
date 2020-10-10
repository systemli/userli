<?php

namespace App\Command;

use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgKeyForUserException;
use App\Handler\OpenPGPWkdHandler;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WkdImportKeyCommand extends Command
{
    /**
     * @var OpenPGPWkdHandler
     */
    private $handler;

    /**
     * @var UserRepository
     */
    private $repository;

    public function __construct(ObjectManager $manager, OpenPGPWkdHandler $handler)
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
            ->setName('app:users:wkd:import-key')
            ->setDescription('Import WKD key for user')
            ->addArgument(
                'email',
                InputOption::VALUE_REQUIRED,
                'email address of the user')
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

        // Check if user exists
        $user = $this->repository->findByEmail($email);
        if (null === $user) {
            throw new \RuntimeException('User not found: '.$email);
        }

        // Read contents from file
        if (!is_file($file)) {
            throw new \RuntimeException('File not found: '.$file);
        }
        $content = file_get_contents($file);

        // Import the key
        try {
            $fingerprint = $this->handler->importKey($user, $content);
        } catch (NoGpgKeyForUserException | MultipleGpgKeysForUserException $e) {
            $output->writeln(sprintf('Error: %s in %s', $e->getMessage(), $file));

            return;
        }

        $output->writeln(sprintf('Imported WKD key for user %s: %s', $user->getEmail(), $fingerprint));
    }
}
