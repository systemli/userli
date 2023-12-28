<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\User;
use App\Enum\Roles;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remove Roles::SPAM from users and disable them.
 */
final class Version20231220203032 extends AbstractMigration implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function getDescription(): string
    {
        return 'Remove Roles::SPAM from users and disable them.';
    }

    public function up(Schema $schema): void
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        /** @var \App\Entity\User[] $users */
        $users = $entityManager->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            if (!$user->hasRole(Roles::SPAM) || $user->isDeleted()) {
                continue;
            }

            $user->setRoles(array_filter($user->getRoles(), function ($role) {
                return $role !== Roles::SPAM;
            }));
            $user->setEnabled(false);
            $entityManager->persist($user);
        }
        $entityManager->flush();
    }

    public function down(Schema $schema): void
    {
    }
}
