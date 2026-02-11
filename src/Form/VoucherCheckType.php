<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\VoucherCheck;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<VoucherCheck>
 */
final class VoucherCheckType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('voucher', TextType::class, [
                'label' => 'form.voucher',
                'attr' => [
                    'autocomplete' => 'off',
                    'maxlength' => 6,
                    'placeholder' => 'form.voucher',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'start.registration-button',
                'attr' => [
                    'class' => 'w-full',
                ],
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VoucherCheck::class,
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'voucher_check';
    }
}
