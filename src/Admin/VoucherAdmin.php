<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Helper\RandomStringGenerator;
use Override;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\DoctrineORMAdminBundle\Filter\DateTimeRangeFilter;
use Sonata\Form\Type\DateRangePickerType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @extends Admin<Voucher>
 */
final class VoucherAdmin extends Admin
{
    public function __construct(protected Security $security)
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
        $object->setUser($this->security->getUser());
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
            ->add('code', null, ['disabled' => !$this->isNewObject()]);
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
            ->add('creationTime')
            ->add('redeemedTime');
    }

    #[Override]
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('user.email', null, ['label' => 'User'])
            ->add('code')
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
