<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\DomainCreate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DomainCreateType extends AbstractType
{
    public const NAME = 'create_domain';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('domain', TextType::class, ['label' => 'form.domain'])
            ->add('submit', SubmitType::class, ['label' => 'form.add']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DomainCreate::class]);
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
