<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Domain;
use App\Enum\Roles;
use DateTime;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class LoadRandomUserData extends AbstractUserData implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $domainRepository = $manager->getRepository(Domain::class);
        $domain = $domainRepository->findOneBy(['name' => 'example.org']);
        $roles = [Roles::USER];

        for ($i = 0; $i < 15000; ++$i) {
            $email = sprintf('user-%d@%s', $i, $domain->getName());

            $user = $this->buildUser($domain, $email, $roles);
            $user->setCreationTime(new DateTime(sprintf('-%s days', random_int(1, 25))));

            if (0 === $i % 20) {
                $user->setDeleted(true);
            }

            if (0 === $i % 30) {
                $user->setRoles(array_merge($user->getRoles(), [Roles::SUSPICIOUS]));
            }

            $manager->persist($user);

            if (($i % 100) === 0) {
                $manager->flush();
            }
        }

        $manager->flush();
        $manager->clear();
    }

    public static function getGroups(): array
    {
        return ['advanced'];
    }
}
