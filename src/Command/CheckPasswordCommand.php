<?php

namespace App\Command;

use App\Handler\UserAuthenticationHandler;
use App\Helper\FileDescriptorReader;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessException;
use Symfony\Component\Process\Process;

/**
 * @author doobry <doobry@systemli.org>
 */
class CheckPasswordCommand extends ContainerAwareCommand
{
    // TODO: put the constants somewhere public and use it here and in RemoveUsersCommand
    const VMAIL_PATH = '/var/vmail/%s/%s';
    const VMAIL_UID = '5000';
    const VMAIL_GID = '5000';

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var FileDescriptorReader
     */
    private $reader;

    /**
     * @var UserAuthenticationHandler
     */
    private $handler;

    /**
     * @var UserRepository
     */
    private $repository;

    /**
     * CheckPasswordCommand constructor.
     *
     * @param ObjectManager             $manager
     * @param FileDescriptorReader      $reader
     * @param UserAuthenticationHandler $handler
     */
    public function __construct(ObjectManager $manager, FileDescriptorReader $reader, UserAuthenticationHandler $handler)
    {
        $this->manager = $manager;
        $this->reader = $reader;
        $this->handler = $handler;
        $this->repository = $this->manager->getRepository('App:User');
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('usrmgmt:users:checkpassword')
            ->setDescription('Checkpassword script for userdb and passdb authentication')
            ->addArgument(
                'checkpassword-reply',
                InputArgument::IS_ARRAY,
                'Optional checkpassword-reply command. Executed if authentication is successful.
                           Set to "/bin/true" to change input file descriptor to STDIN for testing purposes.'
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $replyArgs = $input->getArgument('checkpassword-reply');

        $replyCommand = null;
        if (0 < count($replyArgs)) {
            $replyCommand = array_shift($replyArgs);
        }

        // Allow easy commandline testing
        if ('/bin/true' === $replyCommand) {
            $contents = $this->reader->readStdin();
        } else {
            $contents = $this->reader->readFd3();
        }

        // Validate checkpassword input from file descriptor
        try {
            // timestamp and extra data are unused nowadays and ignored
            list($email, $password, $timestamp, $extra) = explode("\x0", $contents, 4);
        } catch (\ErrorException $e) {
            throw new InvalidArgumentException('Invalid input format. See https://cr.yp.to/checkpwd/interface.html for documentation of the checkpassword interface.');
        }

        if (empty($email) || empty($password)) {
            throw new InvalidArgumentException('Invalid input format. See https://cr.yp.to/checkpwd/interface.html for documentation of the checkpassword interface.');
        }

        // Detect if invoked as userdb lookup by dovecot (with env var AUTHORIZED='1')
        // See https://wiki2.dovecot.org/AuthDatabase/CheckPassword#Checkpassword_as_userdb
        $userDbLookup = ('1' === getenv('AUTHORIZED')) ? true : null;

        // Check if user exists
        $user = $this->repository->findByEmail($email);
        if (null === $user) {
            // Return '3' for non-existent user when doing userdb lookup for dovecot
            if (true === $userDbLookup) {
                return 3;
            } else {
                return 1;
            }
        }

        // Check if authentication credentials are valid
        if (null === $user = $this->handler->authenticate($user, $password)) {
            // TODO: return 111 in case of temporary lookup failure
            return 1;
        }

        // Set default environment variables for checkpassword-reply command
        $envVars = [
            'USER' => $email,
            'HOME' => sprintf(self::VMAIL_PATH, $user->getDomain(), $email),
            'userdb_uid' => self::VMAIL_UID,
            'userdb_gid' => self::VMAIL_GID,
        ];

        // Optionally set quota environment variable for checkpassword-reply command
        if (null === $user->getQuota()) {
            $envVars['EXTRA'] = 'userdb_uid userdb_gid';
        } else {
            $envVars['EXTRA'] = 'userdb_uid userdb_gid userdb_quota_rule';
            $envVars['userdb_quota_rule'] = sprintf('*:storage=%dM', $user->getQuota());
        }

        // Optionally set environment variable AUTHORIZED for dovecot userdb lookup
        // See https://wiki2.dovecot.org/AuthDatabase/CheckPassword#Checkpassword_as_userdb
        if (true === $userDbLookup) {
            $envVars['AUTHORIZED'] = '2';
        }

        if (null === $replyCommand) {
            return 0;
        }

        // Execute checkpassword-reply command
        $replyProcess = new Process(
            $replyCommand.' '.implode(' ', $replyArgs)
        );
        $replyProcess->inheritEnvironmentVariables(true);
        $replyProcess->setEnv($envVars);
        try {
            $replyProcess->mustRun();
        } catch (ProcessException $e) {
            throw new \Exception(sprintf('Error at executing checkpassword-reply command %s: %s', $replyCommand, $replyProcess->getErrorOutput()));
        }

        return 0;
    }
}
