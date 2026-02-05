<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\User;
use App\Enum\MailCrypt;
use App\Enum\Roles;
use App\Handler\MailCryptKeyHandler;
use App\Helper\PasswordUpdater;
use App\Traits\DomainGuesserAwareTrait;
use App\Validator\PasswordPolicy;
use Exception;
use Override;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\Form\Type\BooleanType;
use Sonata\Form\Type\DateRangePickerType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends Admin<User>
 */
final class UserAdmin extends Admin
{
    use DomainGuesserAwareTrait;

    private PasswordUpdater $passwordUpdater;

    private MailCryptKeyHandler $mailCryptKeyHandler;

    private MailCrypt $mailCrypt;

    private Security $security;

    #[Override]
    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'user';
    }

    #[Override]
    protected function createNewInstance(): User
    {
        $user = new User('');
        $user->setRoles([Roles::USER]);
        $user->setPasswordChangeRequired(true);

        return $user;
    }

    #[Override]
    protected function configureFormFields(FormMapper $form): void
    {
        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $user = $this->getRoot()->getSubject();
        $userId = $user->getId();

        // Determine which roles the current user is allowed to assign
        $availableRoles = Roles::getReachableRoles($currentUser?->getRoles() ?? []);
        $availableRoleChoices = array_combine($availableRoles, $availableRoles) ?: [];

        $form
            ->add('email', EmailType::class, ['disabled' => !$this->isNewObject()])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'form.password',
                'required' => $this->isNewObject(),
                'mapped' => false,
                'disabled' => (null !== $userId) ? $user->hasMailCryptSecretBox() : false,
                'help' => (null !== $userId && $user->hasMailCryptSecretBox()) ?
                    'Disabled because user has a MailCrypt key pair defined' : null,
                'constraints' => [
                    new Assert\NotBlank(groups: ['create']),
                    new PasswordPolicy(),
                    new Assert\NotCompromisedPassword(skipOnError: true),
                ],
            ])
            ->add('totp_confirmed', CheckboxType::class, [
                'label' => 'form.twofactor',
                'required' => false,
                'data' => (null !== $userId) ? $user->isTotpAuthenticationEnabled() : false,
                'disabled' => null === $userId || !$user->isTotpAuthenticationEnabled(),
                'help' => 'Can only be enabled by user',
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => $availableRoleChoices,
                'multiple' => true,
                'expanded' => false,
                'label' => 'form.roles',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\NotNull(),
                ],
            ])
            ->add('quota', null, [
                'help' => 'Custom mailbox quota in MB',
            ])
            ->add('passwordChangeRequired', CheckboxType::class, [
                'required' => false,
                'help' => 'User must change password on next login',
            ])
            ->add('deleted', CheckboxType::class, ['disabled' => true]);
    }

    #[Override]
    protected function configureFormOptions(array &$formOptions): void
    {
        $formOptions['validation_groups'] = $this->isNewObject()
            ? ['Default', 'create']
            : ['Default', 'edit'];
    }

    #[Override]
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('email', null, [
                'show_filter' => true,
            ])
            ->add('domain', null, [
                'show_filter' => true,
            ])
            ->add('creationTime', DateTimeRangeFilter::class, [
                'field_type' => DateRangePickerType::class,
                'field_options' => [
                    'field_options' => [
                        'format' => 'dd.MM.yyyy',
                    ],
                ],
            ])
            ->add('roles', null, [
                'field_options' => [
                    'required' => false,
                    'choices' => [Roles::getAll()],
                ],
                'field_type' => ChoiceType::class,
                'show_filter' => true,
            ])
            ->add('recoverySecretBox', CallbackFilter::class, [
                'field_type' => BooleanType::class,
                'label' => 'Recovery Token',
                'callback' => static function (ProxyQuery $proxyQuery, $alias, $field, $value) {
                    if (is_array($value) && 2 === $value['value']) {
                        $query = sprintf('%s.recoverySecretBox IS NULL', $alias);
                    } else {
                        $query = sprintf('%s.recoverySecretBox IS NOT NULL', $alias);
                    }

                    $proxyQuery->getQueryBuilder()->andWhere($query);

                    return true;
                },
            ])
            ->add('mailCryptEnabled')
            ->add('passwordChangeRequired')
            ->add('deleted');
    }

    #[Override]
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, [
                'route' => [
                    'name' => 'edit',
                ],
            ])
            ->addIdentifier('email', null, [
                'route' => [
                    'name' => 'edit',
                ],
            ])
            ->add('creationTime')
            ->add('updatedTime')
            ->add('isTotpAuthenticationEnabled', 'boolean', [
                'label' => 'form.twofactor-short',
            ])
            ->add('recoverySecretBox', 'boolean', [
                'label' => 'Recovery Token',
            ])
            ->add('mailCryptEnabled')
            ->add('deleted');
    }

    #[Override]
    protected function configureBatchActions($actions): array
    {
        if ($this->hasRoute('edit') && $this->hasAccess('edit')) {
            $actions['removeVouchers'] = [
                'ask_confirmation' => true,
            ];
        }

        return $actions;
    }

    /**
     * @throws Exception
     */
    #[Override]
    protected function prePersist(object $object): void
    {
        assert($object instanceof User);
        $plainPassword = $this->getForm()->get('plainPassword')->getData();
        $this->passwordUpdater->updatePassword($object, $plainPassword);
        if (null !== $object->getMailCryptEnabled()) {
            $this->mailCryptKeyHandler->create($object, $plainPassword, $this->mailCrypt->isAtLeast(MailCrypt::ENABLED_ENFORCE_NEW_USERS));
        }

        if (null === $object->getDomain() && null !== $domain = $this->domainGuesser->guess($object->getEmail())) {
            $object->setDomain($domain);
        }
    }

    #[Override]
    protected function preUpdate(object $object): void
    {
        assert($object instanceof User);
        // Only admins are allowed to set attributes of other admins
        if (!$this->security->isGranted(Roles::ADMIN) && $object->hasRole(Roles::ADMIN)) {
            throw new AccessDeniedException('Not allowed to edit admin user');
        }

        $plainPassword = $this->getForm()->get('plainPassword')->getData();
        if (!empty($plainPassword)) {
            $this->passwordUpdater->updatePassword($object, $plainPassword);
        } else {
            $object->updateUpdatedTime();
        }

        if (false === $object->getTotpConfirmed()) {
            $object->setTotpSecret(null);
            $object->setTotpConfirmed(false);
            $object->setTotpBackupCodes([]);
        }
    }

    public function setPasswordUpdater(PasswordUpdater $passwordUpdater): void
    {
        $this->passwordUpdater = $passwordUpdater;
    }

    public function setMailCryptKeyHandler(MailCryptKeyHandler $mailCryptKeyHandler): void
    {
        $this->mailCryptKeyHandler = $mailCryptKeyHandler;
    }

    public function setMailCryptVar(string $mailCrypt): void
    {
        $this->mailCrypt = MailCrypt::from((int) $mailCrypt);
    }

    public function setSecurity(Security $security): void
    {
        $this->security = $security;
    }
}
