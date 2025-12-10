<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\TwofactorConfirm;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<TwofactorConfirm>
 */
final class TwofactorConfirmType extends AbstractType
{
    public const NAME = 'twofactor_confirm';

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'required' => true,
                'label' => 'form.twofactor-login-auth-code',
            ])
            ->add('submit', SubmitType::class, ['label' => 'form.verify']);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TwofactorConfirm::class]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
