<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Domain;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

final class LoadDomainData extends Fixture implements FixtureGroupInterface
{
    private array $domains = [
        'example.org',
        'example.com',
    ];

    #[Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->domains as $name) {
            $domain = new Domain();
            $domain->setName($name);

            $manager->persist($domain);
        }

        $manager->flush();
        $manager->clear();
    }

    #[Override]
    public static function getGroups(): array
    {
        return ['basic'];
    }
}
