<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Helper\RandomStringGenerator;
use App\Validator\UniqueField;
use Override;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ModelFilter;
use Sonata\Form\Type\DateRangePickerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @extends Admin<Voucher>
 */
final class VoucherAdmin extends Admin
{
    public function __construct(private readonly Security $security)
    {
        parent::__construct();
    }

    #[Override]
    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'voucher';
    }

    #[Override]
    protected function createNewInstance(): object
    {
        return new Voucher(RandomStringGenerator::generate(6, true));
    }

    #[Override]
    protected function alterNewInstance(object $object): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $object->setUser($user);
        $object->setDomain($user->getDomain());
    }

    #[Override]
    protected function configureFormFields(FormMapper $form): void
    {
        $disabled = true;
        if ($this->hasAccess('list')) {
            $disabled = false;
        }

        $form
            ->add('user', ModelAutocompleteType::class, [
                'disabled' => $disabled,
                'property' => 'email',
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Callback(static function (?User $user, ExecutionContextInterface $context): void {
                        if (null !== $user && $user->hasRole(Roles::SUSPICIOUS)) {
                            $context->addViolation('voucher.suspicious-user');
                        }
                    }),
                ],
            ])
            ->add('domain', EntityType::class, [
                'class' => Domain::class,
                'choice_label' => 'name',
            ])
            ->add(
                'code',
                null,
                [
                    'disabled' => !$this->isNewObject(),
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(exactly: 6),
                        new UniqueField(entityClass: Voucher::class, field: 'code', message: 'form.unique-field'),
                    ],
                ]
            );
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
            ->add('code')
            ->add('user')
            ->add('domain', null, [
                'associated_property' => 'name',
            ])
            ->add('creationTime')
            ->add('redeemedTime');
    }

    #[Override]
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('user.email', null, ['label' => 'User'])
            ->add('code')
            ->add('domain', ModelFilter::class, [
                'field_type' => EntityType::class,
                'field_options' => [
                    'class' => Domain::class,
                    'choice_label' => 'name',
                ],
            ])
            ->add('creationTime', DateTimeRangeFilter::class, [
                'field_type' => DateRangePickerType::class,
                'field_options' => [
                    'field_options' => [
                        'format' => 'dd.MM.yyyy',
                    ],
                ],
            ])
            ->add('redeemedTime', DateTimeRangeFilter::class, [
                'field_type' => DateRangePickerType::class,
                'field_options' => [
                    'field_options' => [
                        'format' => 'dd.MM.yyyy',
                    ],
                ],
            ]);
    }

    #[Override]
    protected function configureBatchActions($actions): array
    {
        return [];
    }
}
