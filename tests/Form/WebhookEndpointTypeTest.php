<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Enum\WebhookEvent;
use App\Form\Model\WebhookEndpointModel;
use App\Form\WebhookEndpointType;
use Symfony\Component\Form\Test\TypeTestCase;

class WebhookEndpointTypeTest extends TypeTestCase
{
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

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);
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

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($model->isEnabled());
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(WebhookEndpointType::class);
        $view = $form->createView();

        $this->assertArrayHasKey('url', $view->children);
        $this->assertArrayHasKey('secret', $view->children);
        $this->assertArrayHasKey('events', $view->children);
        $this->assertArrayHasKey('enabled', $view->children);
        $this->assertArrayHasKey('submit', $view->children);
    }

    public function testEventsFieldIsExpandedAndMultiple(): void
    {
        $form = $this->factory->create(WebhookEndpointType::class);

        $eventsConfig = $form->get('events')->getConfig();
        $this->assertTrue($eventsConfig->getOption('expanded'));
        $this->assertTrue($eventsConfig->getOption('multiple'));
    }
}
