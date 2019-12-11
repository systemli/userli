<?php

namespace App\Command;

use App\Enum\Roles;
use App\Handler\MailCryptKeyHandler;
use App\Handler\UserAuthenticationHandler;
use App\Helper\FileDescriptorReader;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CheckPasswordCommand extends Command
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
     * @var MailCryptKeyHandler
     */
    private $mailCryptKeyHandler;

    /**
     * @var int
     */
    private $mailCrypt;

    /**
     * CheckPasswordCommand constructor.
     */
    public function __construct(ObjectManager $manager,
                                FileDescriptorReader $reader,
                                UserAuthenticationHandler $handler,
                                MailCryptKeyHandler $mailCryptKeyHandler,
                                int $mailCrypt)
    {
        $this->manager = $manager;
        $this->reader = $reader;
        $this->handler = $handler;
        $this->repository = $this->manager->getRepository('App:User');
        $this->mailCryptKeyHandler = $mailCryptKeyHandler;
        $this->mailCrypt = $mailCrypt;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:users:checkpassword')
            ->setDescription('Checkpassword script for UserDB and PassDB authentication')
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
            $inputStream = $this->reader->readStdin();
        } else {
            $inputStream = $this->reader->readFd3();
        }

        // Validate checkpassword input from file descriptor
        $inputArgs = explode("\x0", $inputStream, 4);

        $email = array_shift($inputArgs);
        $password = array_shift($inputArgs);
        // timestamp and extra data are unused nowadays and ignored
        //$timestamp = array_shift($inputArgs);
        //$extra = array_shift($inputArgs);

        // Verify if an email address has been passed
        if (empty($email)) {
            throw new InvalidArgumentException('Invalid input format: missing argument email. See https://cr.yp.to/checkpwd/interface.html for documentation of the checkpassword interface.');
        }

        // Detect if invoked as UserDB lookup by dovecot (with env var AUTHORIZED='1')
        // See https://wiki2.dovecot.org/AuthDatabase/CheckPassword#Checkpassword_as_userdb
        $userDbLookup = ('1' === getenv('AUTHORIZED')) ? true : false;

        if (false === $userDbLookup && empty($password)) {
            throw new InvalidArgumentException('Invalid input format: missing argument password. See https://cr.yp.to/checkpwd/interface.html for documentation of the checkpassword interface.');
        }

        // Check if user exists
        $user = $this->repository->findByEmail($email);
        if (null === $user) {
            // Return '3' for non-existent user when doing UserDB lookup for dovecot
            if (true === $userDbLookup) {
                return 3;
            } else {
                return 1;
            }
        }

        // block spammers
        if ($user->hasRole(Roles::SPAM)) {
            return 1;
        }

        // Check if authentication credentials are valid
        if (false === $userDbLookup && null === $user = $this->handler->authenticate($user, $password)) {
            // TODO: return 111 in case of temporary lookup failure
            return 1;
        }

        // get email parts
        $emailParts = explode('@', "$email");
        $username = $emailParts[0];
        $domain = $emailParts[1];

        // Set default environment variables for checkpassword-reply command
        $envVars = [
            'USER' => $email,
            'HOME' => sprintf(self::VMAIL_PATH, $domain, $username),
            'userdb_uid' => self::VMAIL_UID,
            'userdb_gid' => self::VMAIL_GID,
        ];

        // set EXTRA env var
        $envVars['EXTRA'] = 'userdb_uid userdb_gid';

        // Optionally set quota environment variable for checkpassword-reply command
        if (null !== $user->getQuota()) {
            $envVars['EXTRA'] = sprintf('%s userdb_quota_rule', $envVars['EXTRA']);
            $envVars['userdb_quota_rule'] = sprintf('*:storage=%dM', $user->getQuota());
        }

        // Optionally create mail_crypt key pair and recovery token (when MAIL_CRYPT >= 3)
        if ($this->mailCrypt >= 3 &&
            false === $user->hasMailCrypt() &&
            null === $user->getMailCryptPublicKey()) {
            $this->mailCryptKeyHandler->create($user);
        }

        // Optionally set mail_crypt environment variables for checkpassword-reply command
        if ($this->mailCrypt >= 1 && $user->hasMailCrypt()) {
            $envVars['EXTRA'] = sprintf('%s userdb_mail_crypt_save_version userdb_mail_crypt_global_public_key', $envVars['EXTRA']);
            $envVars['userdb_mail_crypt_save_version'] = '2';
            $envVars['userdb_mail_crypt_global_public_key'] = $user->getMailCryptPublicKey();
            if (false === $userDbLookup) {
                $envVars['EXTRA'] = sprintf('%s userdb_mail_crypt_global_private_key', $envVars['EXTRA']);
                $envVars['userdb_mail_crypt_global_private_key'] = $this->mailCryptKeyHandler->decrypt($user, $password);
            }
        }

        // Optionally set environment variable AUTHORIZED for dovecot UserDB lookup
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
        $newEnv = array_merge([getenv()], $envVars);
        $replyProcess->setEnv($newEnv);
        try {
            $replyProcess->run();
        } catch (ProcessFailedException $e) {
            throw new \Exception(sprintf('Error at executing checkpassword-reply command %s: %s', $replyCommand, $replyProcess->getErrorOutput()));
        }

        return $replyProcess->getExitCode();
    }
}
