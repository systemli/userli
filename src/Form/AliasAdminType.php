<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\AliasAdminModel;
use App\Validator\EmailAllowedSymbols;
use App\Validator\EmailDomain;
use App\Validator\Lowercase;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<AliasAdminModel>
 */
final class AliasAdminType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['is_edit']) {
            $builder->add('source', EmailType::class, [
                'disabled' => true,
            ]);
        } else {
            $builder->add('source', EmailType::class, [
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Email(mode: 'strict'),
                    new Lowercase(),
                    new EmailAllowedSymbols(),
                    new EmailDomain(),
                ],
            ]);
        }

        $builder->add('user', UserAutocompleteType::class, [
            'required' => false,
        ]);

        if ($options['is_admin']) {
            $builder
                ->add('destination', EmailType::class, [
                    'required' => false,
                ])
                ->add('smtpQuotaLimits', SmtpQuotaLimitsType::class, [
                    'required' => false,
                ]);
        }

        $builder->add('submit', SubmitType::class);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AliasAdminModel::class,
            'is_admin' => false,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_admin', 'bool');
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
