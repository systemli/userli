<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\RecoveryTokenConfirm;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<RecoveryTokenConfirm>
 */
final class RecoveryTokenConfirmType extends AbstractType
{
    public const NAME = 'recovery_token_confirm';

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('confirm', CheckboxType::class, [
                'required' => true,
                'label' => 'form.registration-recovery-token-confirm',
            ])
            ->add('recoveryToken', HiddenType::class)
            ->add('submit', SubmitType::class, ['label' => 'form.registration-recovery-token-next-button']);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RecoveryTokenConfirm::class]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
