<?php

declare(strict_types=1);

namespace App\Form;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<string, int>>
 */
final class SmtpQuotaLimitsType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('per_hour', IntegerType::class, [
                'label' => 'Per Hour',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('per_day', IntegerType::class, [
                'label' => 'Per Day',
                'required' => false,
                'attr' => ['min' => 0],
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
        ]);
    }
}
