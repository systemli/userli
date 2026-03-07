<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Enum\WebhookEvent;
use App\Form\DataTransformer\DomainsToIdsTransformer;
use App\Form\DomainsAutocompleteType;
use App\Form\Model\WebhookEndpointModel;
use App\Form\WebhookEndpointType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WebhookEndpointTypeTest extends TypeTestCase
{
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->urlGenerator->method('generate')->willReturn('/settings/domains/search');
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $domainsAutocomplete = new DomainsAutocompleteType(
            new DomainsToIdsTransformer($this->entityManager),
            $this->urlGenerator,
        );

        return [
            new PreloadedExtension([$domainsAutocomplete], []),
        ];
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
            'domains' => '',
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
        self::assertArrayHasKey('domains', $view->children);
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

    public function testDomainsFieldIsAutocomplete(): void
    {
        $form = $this->factory->create(WebhookEndpointType::class);
        $view = $form->createView();

        self::assertContains('domains_autocomplete', $view->children['domains']->vars['block_prefixes']);
    }

    public function testDomainsFieldHasAutocompleteVars(): void
    {
        $form = $this->factory->create(WebhookEndpointType::class);
        $view = $form->createView();

        self::assertArrayHasKey('autocomplete_url', $view->children['domains']->vars);
        self::assertSame('name', $view->children['domains']->vars['autocomplete_label_field']);
        self::assertSame(0, $view->children['domains']->vars['autocomplete_min_chars']);
        self::assertIsArray($view->children['domains']->vars['autocomplete_selected']);
    }
}
