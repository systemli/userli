<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\AliasAdminType;
use App\Form\DataTransformer\UserToIdTransformer;
use App\Form\Model\AliasAdminModel;
use App\Form\SmtpQuotaLimitsType;
use App\Form\UserAutocompleteType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validation;

class AliasAdminTypeTest extends TypeTestCase
{
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->urlGenerator->method('generate')->willReturn('/settings/users/search');
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $userAutocomplete = new UserAutocompleteType(
            new UserToIdTransformer($this->entityManager),
            $this->urlGenerator,
        );

        $validator = Validation::createValidator();

        return [
            new PreloadedExtension([$userAutocomplete, new SmtpQuotaLimitsType()], []),
            new ValidatorExtension($validator),
        ];
    }

    public function testCreateFormHasExpectedFields(): void
    {
        $form = $this->factory->create(AliasAdminType::class, null, [
            'is_admin' => true,
            'is_edit' => false,
        ]);
        $view = $form->createView();

        self::assertArrayHasKey('source', $view->children);
        self::assertArrayHasKey('user', $view->children);
        self::assertArrayHasKey('destination', $view->children);
        self::assertArrayHasKey('smtpQuotaLimits', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testNonAdminFormHidesAdminFields(): void
    {
        $form = $this->factory->create(AliasAdminType::class, null, [
            'is_admin' => false,
            'is_edit' => false,
        ]);
        $view = $form->createView();

        self::assertArrayHasKey('source', $view->children);
        self::assertArrayHasKey('user', $view->children);
        self::assertArrayNotHasKey('destination', $view->children);
        self::assertArrayNotHasKey('smtpQuotaLimits', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testEditFormHasDisabledSource(): void
    {
        $form = $this->factory->create(AliasAdminType::class, null, [
            'is_admin' => true,
            'is_edit' => true,
        ]);

        $sourceConfig = $form->get('source')->getConfig();
        self::assertTrue($sourceConfig->getOption('disabled'));
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'source' => 'alias@example.org',
            'user' => '',
            'destination' => 'dest@example.org',
            'smtpQuotaLimits' => ['per_hour' => 100, 'per_day' => 1000],
        ];

        $model = new AliasAdminModel();
        $form = $this->factory->create(AliasAdminType::class, $model, [
            'is_admin' => true,
            'is_edit' => true,
        ]);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        // Source is disabled in edit mode, so it won't be submitted
        self::assertSame('dest@example.org', $model->getDestination());
        self::assertSame(['per_hour' => 100, 'per_day' => 1000], $model->getSmtpQuotaLimits());
    }
}
