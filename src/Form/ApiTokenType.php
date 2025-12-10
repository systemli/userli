<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\ApiScope;
use App\Form\Model\ApiToken;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ApiToken>
 */
final class ApiTokenType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = array_combine(ApiScope::all(), ApiScope::all());

        $builder
            ->add('name')
            ->add('scopes', ChoiceType::class, [
                'choices' => $choices,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('submit', SubmitType::class);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApiToken::class,
        ]);
    }
}
