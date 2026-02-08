<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Domain;
use App\Enum\Roles;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

final class LoadRandomUserData extends AbstractUserData implements FixtureGroupInterface
{
    private const int BATCH_SIZE = 500;

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $domain = $manager->getRepository(Domain::class)->findOneBy(['name' => 'example.org']);
        $roles = [Roles::USER];

        for ($i = 0; $i < 15000; ++$i) {
            $user = $this->buildUser($domain, sprintf('user-%d@%s', $i, $domain->getName()), $roles);
            $user->setCreationTime(new DateTimeImmutable(sprintf('-%s days', random_int(1, 25))));

            if (0 === $i % 20) {
                $user->setDeleted(true);
            }

            if (0 === $i % 30) {
                $user->setRoles(array_merge($user->getRoles(), [Roles::SUSPICIOUS]));
            }

            $manager->persist($user);

            if (($i % self::BATCH_SIZE) === 0) {
                $manager->flush();
                $manager->clear();
                // Re-fetch domain after clear
                $domain = $manager->getRepository(Domain::class)->findOneBy(['name' => 'example.org']);
            }
        }

        $manager->flush();
        $manager->clear();
    }

    #[Override]
    public static function getGroups(): array
    {
        return ['advanced'];
    }
}
