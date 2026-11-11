<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\AnnouncementModel;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<AnnouncementModel>
 */
final class AnnouncementType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['is_admin']) {
            $builder->add('domain', DomainAutocompleteType::class);
        }

        $builder
            ->add('subject', TextType::class)
            ->add('body', TextareaType::class)
            ->add('previewFingerprint', HiddenType::class, ['mapped' => false])
            ->add('sendToken', HiddenType::class, ['mapped' => false])
            ->add('preview', SubmitType::class)
            ->add('test', SubmitType::class)
            ->add('send', SubmitType::class);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnnouncementModel::class,
            'is_admin' => false,
        ]);
        $resolver->setAllowedTypes('is_admin', 'bool');
    }
}
