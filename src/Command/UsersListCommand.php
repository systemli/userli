<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Enum\Roles;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

#[AsCommand(name: 'app:users:list', description: 'List users')]
class UsersListCommand extends Command
{
    private readonly UserRepository $repository;

    public function __construct(
        public readonly EntityManagerInterface $manager,
        private readonly RoleHierarchyInterface $roleHierarchy,
    ) {
        $this->repository = $manager->getRepository(User::class);
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'inactive-days',
                'i',
                InputOption::VALUE_OPTIONAL,
                'List users inactive for X days (ignores admins and users with ROLE_PERMANENT)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inactiveDays = $input->getOption('inactive-days');
        if (!empty($inactiveDays) && !is_numeric($inactiveDays)) {
            throw new RuntimeException('Inactive days argument needs to be a number');
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

        return 0;
    }
}
