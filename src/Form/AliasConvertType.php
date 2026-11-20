<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\AliasConvert;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<AliasConvert>
 */
final class AliasConvertType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'required' => true,
            'first_options' => [
                'label' => 'form.password',
            ],
            'second_options' => [
                'label' => 'form.password_confirmation',
            ],
            'invalid_message' => 'different-password',
        ]);

        $builder->add('submit', SubmitType::class);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AliasConvert::class]);
    }
}
