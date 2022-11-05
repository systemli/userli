<?php

namespace App\Tests\Builder;

use App\Builder\RecoveryProcessMessageBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class RecoveryProcessMessageBuilderTest.
 */
class RecoveryProcessMessageBuilderTest extends TestCase
{
    private const BODY_TEMPLATE = 'APP URL: %s'.PHP_EOL.'Project Name: %s';

    private string $email = 'user@example.org';
    private string $appUrl = 'https://www.example.org';
    private string $projectName = 'example.org';

    public function testBuildBody(): void
    {
        $time = 'NOW';

        $builder = $this->getBuilder($this->appUrl, $this->projectName);
        $expected = sprintf(self::BODY_TEMPLATE, $this->appUrl, $this->projectName);

        self::assertEquals($expected, $builder->buildBody('de', $this->email, $time));
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
    private function getBuilder($appUrl, $projectName): RecoveryProcessMessageBuilder
    {
        $translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message = sprintf(self::BODY_TEMPLATE, $appUrl, $projectName);

        $translator->method('trans')->willReturn($message);

        return new RecoveryProcessMessageBuilder($translator, $appUrl, $projectName);
    }
}
