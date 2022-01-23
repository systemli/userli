<?php

namespace App\Command;

use App\Enum\Roles;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class UserListCommand extends Command
{
    /**
     * @var UserRepository
     */
    private $repository;

    /**
     * @var RoleHierarchyInterface
     */
    private $roleHierarchy;

    /**
     * RegistrationMailCommand constructor.
     */
    public function __construct(ObjectManager $manager, RoleHierarchyInterface $roleHierarchy)
    {
        $this->repository = $manager->getRepository('App:User');
        $this->roleHierarchy = $roleHierarchy;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:users:list')
            ->setDescription('List users')
            ->addOption(
                'inactive-days',
                'i',
                InputOption::VALUE_OPTIONAL,
                'List users inactive for X days (ignores admins and users with ROLE_PERMANENT)');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $inactiveDays = $input->getOption('inactive-days');
        if (!empty($inactiveDays) && !is_numeric($inactiveDays)) {
            throw new \RuntimeException('Inactive days argument needs to be a number');
        }

        if (!isset($inactiveDays)) {
            $users = $this->repository->findAll();
        } else {
            $usersAll = $this->repository->findInactiveUsers((int) $inactiveDays);
            $users = [];
            // Exclude accounts with ROLE_PERMANENT
            foreach ($usersAll as $user) {
                if (!in_array(Roles::PERMANENT, $this->roleHierarchy->getReachableRoleNames($user->getRoles()), true)) {
                    $users[] = $user;
                }
            }
        }

        foreach ($users as $user) {
            $output->write(sprintf("%s\n", $user->getEmail()));
        }
    }
}
