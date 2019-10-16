<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomAliasCreateType extends AbstractType
{
    const NAME = 'create_custom_alias';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('alias', TextType::class, array('label' => 'form.new-custom-alias'))
            ->add('submit', SubmitType::class, array('label' => 'form.create-custom-alias'));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => 'App\Form\Model\AliasCreate']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
