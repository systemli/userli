<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\Alias;
use App\Enum\Roles;
use App\Traits\DomainGuesserAwareTrait;
use App\Validator\EmailAddress;
use App\Validator\Lowercase;
use Override;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends Admin<Alias>
 */
final class AliasAdmin extends Admin
{
    use DomainGuesserAwareTrait;

    public function __construct(private Security $security)
    {
        parent::__construct();
    }

    #[Override]
    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'alias';
    }

    #[Override]
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('source', EmailType::class, [
                'constraints' => $this->isNewObject() ? [
                    new Assert\NotNull(),
                    new Assert\Email(mode: 'strict'),
                    new Lowercase(),
                    new EmailAddress(),
                ] : [],
            ])
            ->add('user', ModelAutocompleteType::class, ['property' => 'email', 'required' => false])
            ->add('deleted', CheckboxType::class, ['disabled' => true]);

        if ($this->security->isGranted(Roles::ADMIN)) {
            $form
                ->add('destination', EmailType::class, ['required' => false]);
        }
    }

    #[Override]
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('source')
            ->add('user.email', null, ['label' => 'User'])
            ->add('domain')
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
            ->addIdentifier('source', null, [
                'route' => [
                    'name' => 'edit',
                ],
            ])
            ->addIdentifier('destination', null, [
                'route' => [
                    'name' => 'edit',
                ],
            ])
            ->addIdentifier('user', null, [
                'route' => [
                    'name' => 'edit',
                ],
            ])
            ->add('domain')
            ->add('creationTime')
            ->add('updatedTime')
            ->add('deleted');
    }

    #[Override]
    protected function configureBatchActions($actions): array
    {
        return [];
    }

    #[Override]
    protected function prePersist(object $object): void
    {
        assert($object instanceof Alias);
        if (null === $object->getDestination()) {
            if (null === $object->getUser()) {
                // set user_id to current user if neither destination nor user_id is given
                $object->setUser($this->security->getUser());
            }

            $object->setDestination($object->getUser()?->getEmail());
        }

        if (null !== $domain = $this->getDomainGuesser()->guess($object->getSource())) {
            $object->setDomain($domain);
        }
    }

    #[Override]
    protected function preUpdate(object $object): void
    {
        assert($object instanceof Alias);
        if (null === $object->getDestination()) {
            $object->setDestination($object->getUser()?->getEmail());
        }

        if (null !== $domain = $this->getDomainGuesser()->guess($object->getSource())) {
            $object->setDomain($domain);
        }

        // domain admins are only allowed to set alias to existing user
        if (!$this->security->isGranted(Roles::ADMIN)) {
            $object->setDestination($object->getUser()?->getEmail());
        }
    }
}
