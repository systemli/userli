<?php

namespace App\DataFixtures;

use App\Entity\Domain;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class LoadDomainData extends Fixture implements FixtureGroupInterface
{
    private array $domains = [
        'example.org',
        'example.com',
    ];

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

    public static function getGroups(): array
    {
        return ['basic'];
    }
}
