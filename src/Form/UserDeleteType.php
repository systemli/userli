<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\Delete;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Delete>
 */
class UserDeleteType extends AbstractType
{
    public const NAME = 'delete_user';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, ['label' => 'form.delete-password'])
            ->add('submit', SubmitType::class, ['label' => 'form.delete-account']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Delete::class]);
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
