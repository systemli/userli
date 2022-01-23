<?php

namespace App\Form;

use App\Form\Model\VoucherCreate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VoucherCreateType extends AbstractType
{
    public const NAME = 'create_voucher';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('submit', SubmitType::class, ['label' => 'form.create-voucher']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => VoucherCreate::class]);
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
