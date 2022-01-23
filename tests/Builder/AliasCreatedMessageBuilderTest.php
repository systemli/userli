<?php

namespace App\Tests\Builder;

use App\Builder\AliasCreatedMessageBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AliasCreatedMessageBuilderTest.
 */
class AliasCreatedMessageBuilderTest extends TestCase
{
    private const BODY_TEMPLATE = 'APP URL: %s'.PHP_EOL.'Project Name: %s';

    private $email = 'user@example.org';
    private $alias = 'alias@example.org';
    private $appUrl = 'https://www.example.org';
    private $projectName = 'example.org';

    public function testBuildBody(): void
    {
        $builder = $this->getBuilder($this->appUrl, $this->projectName);
        $expected = sprintf(self::BODY_TEMPLATE, $this->appUrl, $this->projectName);

        self::assertEquals($expected, $builder->buildBody('de', $this->email, $this->alias));
    }

    public function testBuildSubject(): void
    {
        $builder = $this->getBuilder($this->appUrl, $this->projectName);
        $expected = sprintf(self::BODY_TEMPLATE, $this->appUrl, $this->projectName);

        self::assertEquals($expected, $builder->buildSubject('de', $this->email));
    }

    /**
     * @param $appUrl
     * @param $projectName
     */
    private function getBuilder($appUrl, $projectName): AliasCreatedMessageBuilder
    {
        /**
         * @var TranslatorInterface|PHPUnit_Framework_MockObject_MockObject
         */
        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = sprintf(self::BODY_TEMPLATE, $appUrl, $projectName);

        $translator->method('trans')->willReturn($message);

        return new AliasCreatedMessageBuilder($translator, $appUrl, $projectName);
    }
}
