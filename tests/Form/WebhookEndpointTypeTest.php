<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Enum\WebhookEvent;
use App\Form\Model\WebhookEndpointModel;
use App\Form\WebhookEndpointType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class WebhookEndpointTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $url = 'https://example.org/webhook';
        $secret = 'my-webhook-secret';
        $events = [WebhookEvent::USER_CREATED->value];

        $formData = [
            'url' => $url,
            'secret' => $secret,
            'events' => $events,
            'enabled' => true,
        ];

        $model = new WebhookEndpointModel();
        $form = $this->factory->create(WebhookEndpointType::class, $model);

        $expected = new WebhookEndpointModel();
        $expected->setUrl($url);
        $expected->setSecret($secret);
        $expected->setEvents($events);
        $expected->setEnabled(true);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $model);
    }

    public function testSubmitWithDisabled(): void
    {
        $formData = [
            'url' => 'https://example.org/webhook',
            'secret' => 'secret',
            'events' => [WebhookEvent::USER_CREATED->value],
            'enabled' => false,
        ];

        $model = new WebhookEndpointModel();
        $form = $this->factory->create(WebhookEndpointType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($model->isEnabled());
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(WebhookEndpointType::class);
        $view = $form->createView();

        self::assertArrayHasKey('url', $view->children);
        self::assertArrayHasKey('secret', $view->children);
        self::assertArrayHasKey('events', $view->children);
        self::assertArrayHasKey('enabled', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testEventsFieldIsExpandedAndMultiple(): void
    {
        $form = $this->factory->create(WebhookEndpointType::class);

        $eventsConfig = $form->get('events')->getConfig();
        self::assertTrue($eventsConfig->getOption('expanded'));
        self::assertTrue($eventsConfig->getOption('multiple'));
    }
}
