<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\User;
use App\Enum\MailCrypt;
use App\Enum\Roles;
use App\Handler\MailCryptKeyHandler;
use App\Helper\PasswordUpdater;
use App\Traits\DomainGuesserAwareTrait;
use Exception;
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

class UserAdmin extends Admin
{
    use DomainGuesserAwareTrait;

    private PasswordUpdater $passwordUpdater;

    private MailCryptKeyHandler $mailCryptKeyHandler;

    private readonly MailCrypt $mailCrypt;

    private readonly Security $security;

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'user';
    }

    protected function alterNewInstance(object $object): void
    {
        /* @var $object User */
        $object->setRoles([Roles::USER]);
        $object->setPasswordChangeRequired(true);
    }

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
                'disabled' => (null !== $userId) ? $user->hasMailCryptSecretBox() : false,
                'help' => (null !== $userId && $user->hasMailCryptSecretBox()) ?
                    'Disabled because user has a MailCrypt key pair defined' : null,
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
                'callback' => function (ProxyQuery $proxyQuery, $alias, $field, $value) {
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
     * @param User $object
     *
     * @throws Exception
     */
    protected function prePersist($object): void
    {
        $this->passwordUpdater->updatePassword($object, $object->getPlainPassword());
        if (null !== $object->getMailCryptEnabled()) {
            $this->mailCryptKeyHandler->create($object, $object->getPlainPassword(), $this->mailCrypt->isAtLeast(MailCrypt::ENABLED_ENFORCE_NEW_USERS));
        }

        if (null === $object->getDomain() && null !== $domain = $this->domainGuesser->guess($object->getEmail())) {
            $object->setDomain($domain);
        }
    }

    /**
     * @param User $object
     */
    protected function preUpdate($object): void
    {
        // Only admins are allowed to set attributes of other admins
        if (!$this->security->isGranted(Roles::ADMIN) && $object->hasRole(Roles::ADMIN)) {
            throw new AccessDeniedException('Not allowed to edit admin user');
        }

        if (!empty($object->getPlainPassword())) {
            $this->passwordUpdater->updatePassword($object, $object->getPlainPassword());
        } else {
            $object->updateUpdatedTime();
        }

        if (false === $object->getTotpConfirmed()) {
            $object->setTotpSecret(null);
            $object->setTotpConfirmed(false);
            $object->clearBackupCodes();
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
