<?php

namespace App\Admin;

use App\Creator\DomainCreator;
use App\Entity\Domain;
use App\Exception\ValidationException;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Security;

class DomainAdmin extends Admin
{
    /**
     * @var DomainCreator
     */
    private $creator;

    public function __construct(string $code, string $class, string $baseControllerName, Security $security, DomainCreator $creator)
    {
        parent::__construct($code, $class, $baseControllerName, $security);
        $this->creator = $creator;
    }

    /**
     * {@inheritdoc}
     */
    protected $baseRoutePattern = 'domain';

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', TextType::class, ['disabled' => !$this->isNewObject()]);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id')
            ->addIdentifier('name')
            ->add('creationTime')
            ->add('updatedTime');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->remove('delete');
    }

    /**
     * @param Domain $object
     *
     * @return Domain object
     *
     * @throws ValidationException
     */
    public function create($object): Domain
    {
        // this is a copy of the parent function to use the DomainCreator instead of ModelMapper
        // TODO: implement ModelMapperInterface or use prePersist and postPersist
        $this->prePersist($object);
        foreach ($this->extensions as $extension) {
            $extension->prePersist($this, $object);
        }

        $result = $this->creator->create($object->getName());
        // BC compatibility
        if (null !== $result) {
            $object = $result;
        }

        $this->postPersist($object);
        foreach ($this->extensions as $extension) {
            $extension->postPersist($this, $object);
        }

        $this->createObjectSecurity($object);

        return $object;
    }
}
