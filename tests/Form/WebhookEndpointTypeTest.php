<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\Domain;
use App\Enum\WebhookEvent;
use App\Form\Model\WebhookEndpointModel;
use App\Form\WebhookEndpointType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;
use Symfony\UX\Autocomplete\Form\AutocompleteChoiceTypeExtension;

#[AllowMockObjectsWithoutExpectations]
class WebhookEndpointTypeTest extends TypeTestCase
{
    private ManagerRegistry $registry;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        $this->registry = $this->createRegistryMock();
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new DoctrineOrmExtension($this->registry),
            new PreloadedExtension([], [
                'Symfony\Component\Form\Extension\Core\Type\ChoiceType' => [
                    new AutocompleteChoiceTypeExtension(new ChecksumCalculator('test-secret')),
                ],
                'Symfony\Component\Form\Extension\Core\Type\TextType' => [
                    new AutocompleteChoiceTypeExtension(new ChecksumCalculator('test-secret')),
                ],
            ]),
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
            'domains' => [],
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

    public function testDomainsFieldIsMultipleAndNotRequired(): void
    {
        $form = $this->factory->create(WebhookEndpointType::class);

        $domainsConfig = $form->get('domains')->getConfig();
        self::assertTrue($domainsConfig->getOption('multiple'));
        self::assertFalse($domainsConfig->getOption('required'));
    }

    private function createRegistryMock(): ManagerRegistry
    {
        $classMetadata = $this->createStub(ClassMetadata::class);
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);
        $classMetadata->method('getTypeOfField')->willReturn('integer');
        $classMetadata->method('getName')->willReturn(Domain::class);

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([$em])
            ->onlyMethods(['execute', 'getResult', 'getSql', 'setFirstResult', 'setMaxResults'])
            ->getMock();
        $query->method('execute')->willReturn([]);
        $query->method('getResult')->willReturn([]);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$em])
            ->onlyMethods(['getQuery'])
            ->getMock();
        $queryBuilder->method('getQuery')->willReturn($query);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('createQueryBuilder')->willReturn($queryBuilder);

        $em->method('getClassMetadata')->willReturn($classMetadata);
        $em->method('getRepository')->willReturn($repository);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);
        $registry->method('getManager')->willReturn($em);

        return $registry;
    }
}
