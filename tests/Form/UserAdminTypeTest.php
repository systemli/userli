<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Enum\Roles;
use App\Form\Model\UserAdminModel;
use App\Form\SmtpQuotaLimitsType;
use App\Form\UserAdminType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class UserAdminTypeTest extends TypeTestCase
{
    private Security&\PHPUnit\Framework\MockObject\Stub $security;

    protected function setUp(): void
    {
        $this->security = $this->createStub(Security::class);
        $this->security->method('isGranted')->willReturn(true);
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new PreloadedExtension([new UserAdminType($this->security), new SmtpQuotaLimitsType()], []),
            new ValidatorExtension($validator),
        ];
    }

    public function testCreateFormHasExpectedFields(): void
    {
        $form = $this->factory->create(UserAdminType::class, null, [
            'is_edit' => false,
        ]);
        $view = $form->createView();

        self::assertArrayHasKey('email', $view->children);
        self::assertArrayHasKey('plainPassword', $view->children);
        self::assertArrayHasKey('roles', $view->children);
        self::assertArrayHasKey('quota', $view->children);
        self::assertArrayHasKey('smtpQuotaLimits', $view->children);
        self::assertArrayHasKey('totpConfirmed', $view->children);
        self::assertArrayHasKey('passwordChangeRequired', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testEditFormHasDisabledEmail(): void
    {
        $form = $this->factory->create(UserAdminType::class, null, [
            'is_edit' => true,
        ]);

        $emailConfig = $form->get('email')->getConfig();
        self::assertTrue($emailConfig->getOption('disabled'));
    }

    public function testCreateFormHasEnabledEmail(): void
    {
        $form = $this->factory->create(UserAdminType::class, null, [
            'is_edit' => false,
        ]);

        $emailConfig = $form->get('email')->getConfig();
        self::assertFalse($emailConfig->getOption('disabled'));
    }

    public function testTotpDisabledWhenNotEdit(): void
    {
        $form = $this->factory->create(UserAdminType::class, null, [
            'is_edit' => false,
            'totp_enabled' => false,
        ]);

        $totpConfig = $form->get('totpConfirmed')->getConfig();
        self::assertTrue($totpConfig->getOption('disabled'));
    }

    public function testTotpDisabledWhenEditButTotpNotEnabled(): void
    {
        $form = $this->factory->create(UserAdminType::class, null, [
            'is_edit' => true,
            'totp_enabled' => false,
        ]);

        $totpConfig = $form->get('totpConfirmed')->getConfig();
        self::assertTrue($totpConfig->getOption('disabled'));
    }

    public function testTotpEnabledWhenEditAndTotpEnabled(): void
    {
        $form = $this->factory->create(UserAdminType::class, null, [
            'is_edit' => true,
            'totp_enabled' => true,
        ]);

        $totpConfig = $form->get('totpConfirmed')->getConfig();
        self::assertFalse($totpConfig->getOption('disabled'));
    }

    public function testPasswordHelpShownForMailCryptEdit(): void
    {
        $form = $this->factory->create(UserAdminType::class, null, [
            'is_edit' => true,
            'has_mail_crypt' => true,
        ]);

        $passwordFirstConfig = $form->get('plainPassword')->get('first')->getConfig();
        self::assertSame('admin.user.form.password.help.mailcrypt', $passwordFirstConfig->getOption('help'));
    }

    public function testPasswordHelpNullWhenNoMailCrypt(): void
    {
        $form = $this->factory->create(UserAdminType::class, null, [
            'is_edit' => true,
            'has_mail_crypt' => false,
        ]);

        $passwordFirstConfig = $form->get('plainPassword')->get('first')->getConfig();
        self::assertNull($passwordFirstConfig->getOption('help'));
    }

    public function testPasswordNotRequiredForEdit(): void
    {
        $form = $this->factory->create(UserAdminType::class, null, [
            'is_edit' => true,
        ]);

        $passwordConfig = $form->get('plainPassword')->getConfig();
        self::assertFalse($passwordConfig->getRequired());
    }

    public function testPasswordRequiredForCreate(): void
    {
        $form = $this->factory->create(UserAdminType::class, null, [
            'is_edit' => false,
        ]);

        $passwordConfig = $form->get('plainPassword')->getConfig();
        self::assertTrue($passwordConfig->getRequired());
    }

    public function testSubmitValidCreateData(): void
    {
        $formData = [
            'email' => 'test@example.org',
            'plainPassword' => [
                'first' => 'securePassword123',
                'second' => 'securePassword123',
            ],
            'roles' => ['ROLE_USER'],
            'quota' => 1024,
            'smtpQuotaLimits' => ['per_hour' => 100, 'per_day' => 1000],
            'passwordChangeRequired' => true,
        ];

        $model = new UserAdminModel();
        $form = $this->factory->create(UserAdminType::class, $model, [
            'is_edit' => false,
            'validation_groups' => false,
        ]);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertSame('test@example.org', $model->getEmail());
        self::assertSame('securePassword123', $model->getPlainPassword());
        self::assertSame(['ROLE_USER'], $model->getRoles());
        self::assertSame(1024, $model->getQuota());
        self::assertSame(['per_hour' => 100, 'per_day' => 1000], $model->getSmtpQuotaLimits());
        self::assertTrue($model->isPasswordChangeRequired());
    }

    public function testSubmitEditDataWithDisabledEmail(): void
    {
        $model = new UserAdminModel();
        $model->setEmail('original@example.org');

        $form = $this->factory->create(UserAdminType::class, $model, [
            'is_edit' => true,
            'validation_groups' => false,
        ]);

        $form->submit([
            'email' => 'changed@example.org',
            'plainPassword' => ['first' => '', 'second' => ''],
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
            'quota' => 2048,
        ]);

        self::assertTrue($form->isSynchronized());
        // Email is disabled in edit mode, so it should not change
        self::assertSame('original@example.org', $model->getEmail());
        self::assertEqualsCanonicalizing(['ROLE_USER', 'ROLE_ADMIN'], $model->getRoles());
        self::assertSame(2048, $model->getQuota());
    }

    public function testAdminSeesAllRoles(): void
    {
        $form = $this->factory->create(UserAdminType::class, null, [
            'is_edit' => false,
        ]);

        $rolesConfig = $form->get('roles')->getConfig();
        $choices = $rolesConfig->getOption('choices');

        self::assertContains(Roles::ADMIN, $choices);
        self::assertContains(Roles::DOMAIN_ADMIN, $choices);
        self::assertContains(Roles::USER, $choices);
        self::assertContains(Roles::PERMANENT, $choices);
        self::assertContains(Roles::SPAM, $choices);
        self::assertContains(Roles::MULTIPLIER, $choices);
        self::assertContains(Roles::SUSPICIOUS, $choices);
    }

    public function testDomainAdminSeesOnlyReachableRoles(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn(false);

        $formType = new UserAdminType($security);

        $formFactory = \Symfony\Component\Form\Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$formType, new SmtpQuotaLimitsType()], []))
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();

        $form = $formFactory->create(UserAdminType::class, null, [
            'is_edit' => false,
        ]);

        $rolesConfig = $form->get('roles')->getConfig();
        $choices = $rolesConfig->getOption('choices');

        self::assertContains(Roles::USER, $choices);
        self::assertContains(Roles::PERMANENT, $choices);
        self::assertNotContains(Roles::ADMIN, $choices);
        self::assertNotContains(Roles::DOMAIN_ADMIN, $choices);
        self::assertNotContains(Roles::SPAM, $choices);
        self::assertNotContains(Roles::MULTIPLIER, $choices);
        self::assertNotContains(Roles::SUSPICIOUS, $choices);
    }

    public function testDomainAdminCannotSubmitAdminRole(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn(false);

        $formType = new UserAdminType($security);

        $formFactory = \Symfony\Component\Form\Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([$formType, new SmtpQuotaLimitsType()], []))
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();

        $model = new UserAdminModel();
        $form = $formFactory->create(UserAdminType::class, $model, [
            'is_edit' => false,
            'validation_groups' => false,
        ]);

        $form->submit([
            'email' => 'test@example.org',
            'plainPassword' => ['first' => 'securePassword123', 'second' => 'securePassword123'],
            'roles' => ['ROLE_ADMIN'],
            'quota' => 1024,
        ]);

        // The form should not be valid because ROLE_ADMIN is not in the choices
        self::assertFalse($form->get('roles')->isValid());
    }
}
