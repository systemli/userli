<?php

namespace AppBundle\Admin;

use AppBundle\Entity\Alias;
use AppBundle\Traits\DomainGuesserAwareTrait;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

/**
 * @author louis <louis@systemli.org>
 */
class AliasAdmin extends Admin
{
    use DomainGuesserAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected $baseRoutePattern = 'alias';

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('source', EmailType::class)
            ->add('destination', EmailType::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('source', null, array(
                'show_filter' => true,
            ))
            ->add('destination', null, array(
                'show_filter' => true,
            ))
            ->add('domain', null, array(
                'show_filter' => false,
            ));
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('source')
            ->add('destination')
            ->add('creationTime')
            ->add('updatedTime');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureBatchActions($actions)
    {
        return array();
    }

    /**
     * @param Alias $alias
     */
    public function preUpdate($alias)
    {
        if (null !== $domain = $this->getDomainGuesser()->guess($alias->getSource())) {
            $alias->setDomain($domain);
        }
    }
}
