<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Enum\Roles;
use App\Form\Model\UserAdminModel;
use App\Validator\Lowercase;
use App\Validator\PasswordPolicy;
use App\Validator\UniqueField;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<UserAdminModel>
 */
final class UserAdminType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];
        $hasMailCrypt = $options['has_mail_crypt'];

        if ($isEdit) {
            $builder->add('email', EmailType::class, [
                'disabled' => true,
            ]);
        } else {
            $builder->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Email(mode: 'strict'),
                    new Lowercase(),
                    new UniqueField(entityClass: User::class, field: 'email', message: 'registration.email-already-taken'),
                ],
            ]);
        }

        $passwordHelp = null;
        if ($isEdit && $hasMailCrypt) {
            $passwordHelp = 'admin.user.form.password.help.mailcrypt';
        }

        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'required' => !$isEdit,
            'first_options' => [
                'label' => 'form.password',
                'help' => $passwordHelp,
                'help_translation_parameters' => [],
            ],
            'second_options' => [
                'label' => 'form.password_confirmation',
            ],
            'invalid_message' => 'different-password',
            'constraints' => array_filter([
                !$isEdit ? new Assert\NotBlank() : null,
                new PasswordPolicy(),
                new Assert\NotCompromisedPassword(skipOnError: true),
            ]),
        ]);

        $highestRole = $this->security->isGranted(Roles::ADMIN) ? [Roles::ADMIN] : [Roles::DOMAIN_ADMIN];
        $availableRoles = Roles::getReachableRoles($highestRole);
        $availableRoleChoices = array_combine($availableRoles, $availableRoles) ?: [];

        $builder->add('roles', ChoiceType::class, [
            'choices' => $availableRoleChoices,
            'multiple' => true,
            'expanded' => true,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\NotNull(),
            ],
        ]);

        $builder->add('quota', IntegerType::class, [
            'required' => false,
        ]);

        $builder->add('smtpQuotaLimits', SmtpQuotaLimitsType::class, [
            'required' => false,
        ]);

        $builder->add('totpConfirmed', CheckboxType::class, [
            'required' => false,
            'disabled' => !$isEdit || !$options['totp_enabled'],
        ]);

        $builder->add('passwordChangeRequired', CheckboxType::class, [
            'required' => false,
        ]);

        $builder->add('submit', SubmitType::class);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserAdminModel::class,
            'is_edit' => false,
            'has_mail_crypt' => false,
            'totp_enabled' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
        $resolver->setAllowedTypes('has_mail_crypt', 'bool');
        $resolver->setAllowedTypes('totp_enabled', 'bool');
    }
}
