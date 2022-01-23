<?php

namespace App\Tests\Builder;

use App\Builder\WelcomeMessageBuilder;
use App\Entity\Domain;
use App\Repository\DomainRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class WelcomeMessageBuilderTest.
 */
class WelcomeMessageBuilderTest extends TestCase
{
    private const BODY_TEMPLATE = 'Domain: %s'.PHP_EOL.'APP URL: %s'.PHP_EOL.'Project Name: %s';

    public function testBuildBody(): void
    {
        $domain = 'example.com';
        $appUrl = 'https://www.example.com';
        $projectUrl = 'https://users.example.com';

        $builder = $this->getBuilder($domain, $appUrl, $projectUrl);
        $expected = sprintf(self::BODY_TEMPLATE, $domain, $appUrl, $projectUrl);

        self::assertEquals($expected, $builder->buildBody('de'));
    }

    public function testBuildSubject(): void
    {
        $domain = 'example.com';
        $appUrl = 'https://www.example.com';
        $projectUrl = 'https://users.example.com';

        $builder = $this->getBuilder($domain, $appUrl, $projectUrl);
        $expected = sprintf(self::BODY_TEMPLATE, $domain, $appUrl, $projectUrl);

        self::assertEquals($expected, $builder->buildSubject('en'));
    }

    /**
     * @param $domain
     * @param $appUrl
     * @param $projectName
     */
    private function getBuilder($domain, $appUrl, $projectName): WelcomeMessageBuilder
    {
        /**
         * @var TranslatorInterface|PHPUnit_Framework_MockObject_MockObject
         */
        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = sprintf(self::BODY_TEMPLATE, $domain, $appUrl, $projectName);

        $translator->method('trans')->willReturn($message);

        return new WelcomeMessageBuilder($translator, $this->getManager(), $appUrl, $projectName);
    }

    /**
     * Manager that returns default domain.
     */
    public function getManager(): ObjectManager
    {
        $manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder(DomainRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $domain = new Domain();
        $domain->setName('example.com');

        $repository->method('getDefaultDomain')
            ->willReturn($domain);

        $manager->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
