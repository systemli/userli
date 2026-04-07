<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\DomainAdminModel;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<DomainAdminModel>
 */
final class DomainEditType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('invitationEnabled', CheckboxType::class, [
                'required' => false,
                'label' => 'admin.domain.form.invitation_enabled',
                'help' => 'admin.domain.form.invitation_enabled_help',
            ])
            ->add('invitationLimit', IntegerType::class, [
                'label' => 'admin.domain.form.invitation_limit',
                'help' => 'admin.domain.form.invitation_limit_help',
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\PositiveOrZero(),
                ],
            ])
            ->add('waitingPeriodDays', IntegerType::class, [
                'label' => 'admin.domain.form.waiting_period_days',
                'help' => 'admin.domain.form.waiting_period_days_help',
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Positive(),
                ],
            ])
            ->add('submit', SubmitType::class);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DomainAdminModel::class,
        ]);
    }
}
