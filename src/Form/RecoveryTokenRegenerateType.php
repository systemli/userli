<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\RecoveryTokenRegenerate;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<RecoveryTokenRegenerate>
 */
final class RecoveryTokenRegenerateType extends AbstractType
{
    public const string NAME = 'recovery_token_regenerate';

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, [
                'label' => 'form.password',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.generate-recovery-token',
            ]);

        if ($options['requires_totp']) {
            $builder->add('totpCode', TextType::class, [
                'label' => 'form.twofactor-code',
                'required' => true,
                'attr' => [
                    'autocomplete' => 'one-time-code',
                    'inputmode' => 'numeric',
                ],
            ]);
        }
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RecoveryTokenRegenerate::class,
            'requires_totp' => false,
        ]);
        $resolver->setAllowedTypes('requires_totp', 'bool');
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
