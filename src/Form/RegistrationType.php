<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\DataTransformer\TextToEmailTransformer;
use App\Form\Model\Registration;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Registration>
 */
final class RegistrationType extends AbstractType
{
    public const string NAME = 'registration';

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transformer = new TextToEmailTransformer($options['domain']);

        $builder
            ->add('voucher', HiddenType::class)
            ->add($builder->create(
                'email',
                TextType::class,
                ['label' => 'registration.label-email']
            )->addViewTransformer($transformer))
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'form.password'],
                'second_options' => ['label' => 'form.password_confirmation'],
                'invalid_message' => 'form.different-password',
            ])
            ->add('submit', SubmitType::class, ['label' => 'form.submit']);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Registration::class]);
        $resolver->setRequired('domain');
        $resolver->setAllowedTypes('domain', 'string');
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
