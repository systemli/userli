<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\PlainPassword;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<PlainPassword>
 */
final class PlainPasswordType extends AbstractType
{
    public const NAME = 'plain_password';

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'form.plain-password'],
                'second_options' => ['label' => 'form.plain-password_confirmation'],
                'invalid_message' => 'form.different-password',
            ])
            ->add('submit', SubmitType::class, ['label' => 'form.submit']);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PlainPassword::class]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
