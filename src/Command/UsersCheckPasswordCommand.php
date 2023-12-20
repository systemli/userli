<?php

namespace App\Command;

use App\Entity\User;
use App\Enum\Roles;
use App\Handler\MailCryptKeyHandler;
use App\Handler\UserAuthenticationHandler;
use App\Helper\FileDescriptorReader;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class UsersCheckPasswordCommand extends Command
{
    private FileDescriptorReader $reader;
    private UserAuthenticationHandler $handler;
    private UserRepository $repository;
    private MailCryptKeyHandler $mailCryptKeyHandler;
    private int $mailCrypt;
    private int $mailUid;
    private int $mailGid;
    private string $mailLocation;

    /**
     * UsersCheckPasswordCommand constructor.
     */
    public function __construct(EntityManagerInterface $manager,
                                FileDescriptorReader $reader,
                                UserAuthenticationHandler $handler,
                                MailCryptKeyHandler $mailCryptKeyHandler,
                                int $mailCrypt,
                                int $mailUid,
                                int $mailGid,
                                string $mailLocation)
    {
        $this->reader = $reader;
        $this->handler = $handler;
        $this->repository = $manager->getRepository(User::class);
        $this->mailCryptKeyHandler = $mailCryptKeyHandler;
        $this->mailCrypt = $mailCrypt;
        $this->mailLocation = $mailLocation;
        $this->mailUid = $mailUid;
        $this->mailGid = $mailGid;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
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
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $replyArgs = $input->getArgument('checkpassword-reply');

        $replyCommand = null;
        if (0 < count($replyArgs)) {
            $replyCommand = $replyArgs[0];
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
        // $timestamp = array_shift($inputArgs);
        // $extra = array_shift($inputArgs);

        // Verify if an email address has been passed
        if (empty($email)) {
            throw new InvalidArgumentException('Invalid input format: missing argument email. See https://cr.yp.to/checkpwd/interface.html for documentation of the checkpassword interface.');
        }

        // Detect if invoked as UserDB lookup by dovecot (with env var AUTHORIZED='1')
        // See https://wiki2.dovecot.org/AuthDatabase/CheckPassword#Checkpassword_as_userdb
        $userDbLookup = '1' === getenv('AUTHORIZED');

        if (false === $userDbLookup && empty($password)) {
            // Instead of throwing an exception, just return 1 (invalid credentials)
            // throw new InvalidArgumentException('Invalid input format: missing argument password. See https://cr.yp.to/checkpwd/interface.html for documentation of the checkpassword interface.');
            return 1;
        }

        // Check if user exists
        $user = $this->repository->findByEmail($email);
        if (null === $user) {
            // Return '3' for non-existent user when doing UserDB lookup for dovecot
            if (true === $userDbLookup) {
                return 3;
            }

            return 1;
        }

        // block spammers from login but not lookup
        if (false === $userDbLookup && ($user->hasRole(Roles::SPAM) || !$user->isEnabled())) {
            return 1;
        }

        // Check if authentication credentials are valid
        if (false === $userDbLookup && null === $user = $this->handler->authenticate($user, $password)) {
            // TODO: return 111 in case of temporary lookup failure
            return 1;
        }

        // get email parts
        [$username, $domain] = explode('@', $email);

        // Set default environment variables for checkpassword-reply command
        $envVars = [
            'USER' => $email,
            'HOME' => $this->mailLocation.DIRECTORY_SEPARATOR.$domain.DIRECTORY_SEPARATOR.$username,
            'userdb_uid' => $this->mailUid,
            'userdb_gid' => $this->mailGid,
        ];

        // set EXTRA env var
        $envVars['EXTRA'] = 'userdb_uid userdb_gid';

        // Optionally set quota environment variable for checkpassword-reply command
        if (null !== $user->getQuota()) {
            $envVars['EXTRA'] = sprintf('%s userdb_quota_rule', $envVars['EXTRA']);
            $envVars['userdb_quota_rule'] = sprintf('*:storage=%dM', $user->getQuota());
        }

        // Optionally create mail_crypt key pair and recovery token
        // (when MAIL_CRYPT >= 3 and not $userDbLookup)
        if ($this->mailCrypt >= 3 &&
            false === $userDbLookup &&
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
        $replyProcess = new Process($replyArgs);
        $replyProcess->setEnv(array_merge(getenv(), $envVars));
        try {
            $replyProcess->run();
        } catch (ProcessFailedException $e) {
            throw new \Exception(sprintf('Error at executing checkpassword-reply command %s: %s', $replyCommand, $replyProcess->getErrorOutput()));
        }

        return $replyProcess->getExitCode();
    }
}
