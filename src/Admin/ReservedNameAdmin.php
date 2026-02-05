<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\ReservedName;
use App\Validator\Lowercase;
use App\Validator\UniqueField;
use Override;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends Admin<ReservedName>
 */
final class ReservedNameAdmin extends Admin
{
    #[Override]
    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'reservedname';
    }

    #[Override]
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', TextType::class, [
                'disabled' => !$this->isNewObject(),
                'constraints' => $this->isNewObject() ? [
                    new Assert\NotNull(),
                    new Assert\NotBlank(),
                    new Lowercase(),
                    new UniqueField(entityClass: ReservedName::class, field: 'name', message: 'form.unique-field'),
                ] : [],
            ]);
    }

    #[Override]
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name');
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
            ->addIdentifier('name', null, [
                'route' => [
                    'name' => 'edit',
                ],
            ])
            ->add('creationTime')
            ->add('updatedTime');
    }
}
