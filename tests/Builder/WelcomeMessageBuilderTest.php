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
    const BODY_TEMPLATE = 'Domain: %s'.PHP_EOL.'APP URL: %s'.PHP_EOL.'Project Name: %s';

    public function testBuildBody()
    {
        $domain = 'example.com';
        $appUrl = 'https://www.example.com';
        $projectUrl = 'https://users.example.com';

        $builder = $this->getBuilder($domain, $appUrl, $projectUrl);
        $expected = sprintf(self::BODY_TEMPLATE, $domain, $appUrl, $projectUrl);

        self::assertEquals($expected, $builder->buildBody('de'));
    }

    public function testBuildSubject()
    {
        $domain = 'example.com';
        $appUrl = 'https://www.example.com';
        $projectUrl = 'https://users.example.com';

        $builder = $this->getBuilder($domain, $appUrl, $projectUrl);
        $expected = sprintf(self::BODY_TEMPLATE, $domain, $appUrl, $projectUrl);

        self::assertEquals($expected, $builder->buildSubject('en'));
    }

    /**
     * @param $appUrl
     * @param $projectName
     *
     * @return WelcomeMessageBuilder
     */
    private function getBuilder($domain, $appUrl, $projectName)
    {
        /**
         * @var TranslatorInterface|PHPUnit_Framework_MockObject_MockObject
         */
        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = sprintf(self::BODY_TEMPLATE, $domain, $appUrl, $projectName);

        $translator->expects($this->any())->method('trans')->willReturn($message);

        $builder = new WelcomeMessageBuilder($translator, $this->getManager(), $appUrl, $projectName);

        return $builder;
    }

    /**
     * Manager that returns default domain.
     */
    public function getManager()
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
            ->will($this->returnValue($domain));

        $manager->expects($this->any())->method('getRepository')->willReturn($repository);

        return $manager;
    }
}
