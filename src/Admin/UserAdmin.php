<?php

namespace App\Admin;

use Exception;
use App\Entity\User;
use App\Enum\Roles;
use App\Handler\MailCryptKeyHandler;
use App\Helper\PasswordUpdater;
use App\Traits\DomainGuesserAwareTrait;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\Form\Type\BooleanType;
use Sonata\Form\Type\DateRangePickerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserAdmin extends Admin
{
    use DomainGuesserAwareTrait;

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function alterNewInstance(object $object): void
    {
        $object->setRoles([Roles::USER]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $form): void
    {
        $user = $this->getRoot()->getSubject();
        $userId = $user->getId();

        $form
            ->add('email', EmailType::class, ['disabled' => !$this->isNewObject()])
            ->add('totp_confirmed', CheckboxType::class, [
                'label' => 'form.twofactor',
                'required' => false,
                'data' => (null !== $userId) ? $user->isTotpAuthenticationEnabled() : false,
                'disabled' => null === $userId || !$user->isTotpAuthenticationEnabled(),
                'help' => 'Can only be enabled by user',
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [Roles::getAll()],
                'multiple' => true,
                'expanded' => false,
                'label' => 'form.roles',
            ])
            ->add('quota', null, [
                'help' => 'Custom mailbox quota in MB',
            ])
            ->add('deleted', CheckboxType::class, ['disabled' => true]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('email')
            ->add('domain')
            ->add('creationTime', DateTimeRangeFilter::class, [
                'field_type' => DateRangePickerType::class,
                'field_options' => [
                    'field_options' => [
                        'format' => 'dd.MM.yyyy'
                    ]
                ]
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
            ->add('mailCrypt')
            ->add('deleted');
    }

    /**
     * {@inheritdoc}
     */
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
            ->add('mailCrypt')
            ->add('deleted');
    }

    /**
     * {@inheritdoc}
     */
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
     */
    public function preUpdate($object): void
    {
        // Only admins are allowed to set attributes of other admins
        if (!$this->security->isGranted(Roles::ADMIN) && $object->hasRole(Roles::ADMIN)) {
            throw new AccessDeniedException('Not allowed to edit admin user');
        }

        $object->updateUpdatedTime();

        if (false === $object->getTotpConfirmed()) {
            $object->setTotpSecret(null);
            $object->setTotpConfirmed(false);
            $object->clearBackupCodes();
        }
    }
}
