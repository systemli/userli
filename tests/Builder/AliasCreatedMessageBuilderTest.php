<?php

namespace App\Tests\Builder;

use App\Builder\AliasCreatedMessageBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AliasCreatedMessageBuilderTest.
 *
 * @author doobry <doobry@systemli.org>
 */
class AliasCreatedMessageBuilderTest extends TestCase
{
    const BODY_TEMPLATE = 'APP URL: %s'.PHP_EOL.'Project Name: %s';

    private $email = 'user@example.org';
    private $alias = 'alias@example.org';
    private $appUrl = 'https://www.example.org';
    private $projectName = 'example.org';

    public function testBuildBody()
    {
        $builder = $this->getBuilder($this->appUrl, $this->projectName);
        $expected = sprintf(self::BODY_TEMPLATE, $this->appUrl, $this->projectName);

        self::assertEquals($expected, $builder->buildBody('de', $this->email, $this->alias));
    }

    public function testBuildSubject()
    {
        $builder = $this->getBuilder($this->appUrl, $this->projectName);
        $expected = sprintf(self::BODY_TEMPLATE, $this->appUrl, $this->projectName);

        self::assertEquals($expected, $builder->buildSubject('de', $this->email));
    }

    /**
     * @param $appUrl
     * @param $projectName
     *
     * @return AliasCreatedMessageBuilder
     */
    private function getBuilder($appUrl, $projectName)
    {
        /**
         * @var TranslatorInterface|PHPUnit_Framework_MockObject_MockObject
         */
        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = sprintf(self::BODY_TEMPLATE, $appUrl, $projectName);

        $translator->expects($this->any())->method('trans')->willReturn($message);

        $builder = new AliasCreatedMessageBuilder($translator, $appUrl, $projectName);

        return $builder;
    }
}
