<?php

namespace App\Admin;

use App\Entity\User;
use App\Enum\Roles;
use App\Handler\DeleteHandler;
use App\Handler\MailCryptKeyHandler;
use App\Helper\PasswordUpdater;
use App\Traits\DomainGuesserAwareTrait;
use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\DateTime;

class UserAdmin extends Admin
{
    use DomainGuesserAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected $baseRoutePattern = 'user';
    /**
     * {@inheritdoc}
     */
    protected $datagridValues = [
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'creationTime',
    ];
    /**
     * @var PasswordUpdater
     */
    private $passwordUpdater;
    /**
     * @var DeleteHandler
     */
    private $deleteHandler;
    /**
     * @var MailCryptKeyHandler
     */
    private $mailCryptKeyHandler;
    /**
     * @var int
     */
    private $mailCrypt;

    /**
     * {@inheritdoc}
     */
    public function getNewInstance(): User
    {
        /** @var User $instance */
        $instance = parent::getNewInstance();

        $instance->setRoles([Roles::USER]);

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $form): void
    {
        $user = $this->getRoot()->getSubject();
        $userId = (null === $user) ? null : $user->getId();

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
            ->add('recovery_secret_box', CheckboxType::class, [
                'label' => 'Recovery Token',
                'data' => (null !== $userId) ? $user->hasRecoverySecretBox() : false,
                'disabled' => true,
                'help' => 'Can only be configured by user',
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
            ->add('mailCrypt', CheckboxType::class, [
                // Default to true for new users if mail_crypt is enabled
                'data' => (null !== $userId) ? $user->hasMailCrypt() : $this->mailCrypt >= 2,
                // Disable for existing users or when mail_crypt is disabled
                'disabled' => null !== $userId || $this->mailCrypt <= 0,
            ])
            ->add('deleted', CheckboxType::class, ['disabled' => true]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('email', null, [
                'show_filter' => true,
            ])
            ->add('domain', null, [
                'show_filter' => false,
            ])
            ->add('registration_since', 'doctrine_orm_callback', [
                'callback' => function (ProxyQuery $proxyQuery, $alias, $field, $value) {
                    if (isset($value['value']) && null !== $value = $value['value']) {
                        /** @var QueryBuilder $qb */
                        $qb = $proxyQuery->getQueryBuilder();
                        $field = sprintf('%s.creationTime', $alias);
                        $qb->andWhere(sprintf('%s >= :datetime', $field))
                            ->setParameter('datetime', new DateTime($value))
                            ->orderBy($field, 'DESC');
                    }

                    return true;
                },
                'field_type' => TextType::class,
                'show_filter' => true,
            ])
            ->add('roles', null, [
                'field_options' => [
                    'required' => false,
                    'choices' => [Roles::getAll()],
                ],
                'field_type' => ChoiceType::class,
                'show_filter' => true,
            ])
            ->add('recoverySecretBox', 'doctrine_orm_callback', [
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
            ->add('mailCrypt', 'doctrine_orm_choice', [
                'field_options' => [
                    'required' => false,
                    'choices' => [0 => 'No', 1 => 'Yes'],
                ],
                'field_type' => ChoiceType::class,
                'show_filter' => true,
            ])
            ->add('deleted', 'doctrine_orm_choice', [
                'field_options' => [
                    'required' => false,
                    'choices' => [0 => 'No', 1 => 'Yes'],
                ],
                'field_type' => ChoiceType::class,
                'show_filter' => true,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->addIdentifier('email')
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
     *
     * @throws \Exception
     */
    public function prePersist($object): void
    {
        $this->passwordUpdater->updatePassword($object);

        if (null !== $object->hasMailCrypt()) {
            $this->mailCryptKeyHandler->create($object);
        }

        if (null === $object->getDomain() && null !== $domain = $this->domainGuesser->guess($object->getEmail())) {
            $object->setDomain($domain);
        }
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

        if (!empty($object->getPlainPassword())) {
            $this->passwordUpdater->updatePassword($object);
        } else {
            $object->updateUpdatedTime();
        }

        if (false === $object->getTotpConfirmed()) {
            $object->setTotpSecret(null);
            $object->setTotpConfirmed(false);
            $object->clearBackupCodes();
        }
    }

    /**
     * @param User $object
     */
    public function delete($object): void
    {
        $this->deleteHandler->deleteUser($object);
    }

    public function setPasswordUpdater(PasswordUpdater $passwordUpdater): void
    {
        $this->passwordUpdater = $passwordUpdater;
    }

    public function setDeleteHandler(DeleteHandler $deleteHandler): void
    {
        $this->deleteHandler = $deleteHandler;
    }

    public function setMailCryptKeyHandler(MailCryptKeyHandler $mailCryptKeyHandler): void
    {
        $this->mailCryptKeyHandler = $mailCryptKeyHandler;
    }

    public function setMailCryptVar(int $mailCrypt): void
    {
        $this->mailCrypt = $mailCrypt;
    }
}
