<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ReservedName;
use App\Validator\Lowercase;
use App\Validator\UniqueField;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<ReservedName>
 */
final class ReservedNameType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\NotBlank(),
                    new Lowercase(),
                    new UniqueField(entityClass: ReservedName::class, field: 'name', message: 'form.unique-field'),
                ],
            ])
            ->add('submit', SubmitType::class);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReservedName::class,
        ]);
    }
}
