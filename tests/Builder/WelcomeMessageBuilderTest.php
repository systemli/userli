<?php

namespace App\Tests\Builder;

use App\Builder\WelcomeMessageBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class WelcomeMessageBuilderTest
 * @package App\Tests\Builder
 * @author louis <louis@systemli.org>
 */
class WelcomeMessageBuilderTest extends TestCase
{
    const BODY_TEMPLATE = 'Domain: %s' . PHP_EOL . 'APP URL: %s' . PHP_EOL . 'Project Name: %s';

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
        $expected = sprintf('Welcome to %s!', $domain);

        self::assertEquals($expected, $builder->buildSubject());
    }

    /**
     * @param $domain
     * @param $appUrl
     * @param $projectName
     * @return WelcomeMessageBuilder
     */
    private function getBuilder($domain, $appUrl, $projectName)
    {
        /**
         * @var TranslatorInterface|PHPUnit_Framework_MockObject_MockObject $translator
         */
        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = sprintf(self::BODY_TEMPLATE, $domain, $appUrl, $projectName);

        $translator->expects($this->any())->method('trans')->willReturn($message);

        $builder = new WelcomeMessageBuilder($translator, $domain, $appUrl, $projectName);

        return $builder;
    }

}