<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Model\AliasCreate;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<AliasCreate>
 */
final class CustomAliasCreateType extends AbstractType
{
    public const string NAME = 'create_custom_alias';

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('alias', TextType::class, ['label' => 'form.new-custom-alias'])
            ->add('domain', HiddenType::class, ['mapped' => false])
            ->add('submit', SubmitType::class, ['label' => 'form.create-custom-alias']);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
            $data = $event->getData();

            if (!is_array($data) || empty($data['alias']) || empty($data['domain'])) {
                return;
            }

            // Combine local part + domain into full email address
            $data['alias'] = strtolower((string) $data['alias']).'@'.$data['domain'];
            $event->setData($data);
        });
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AliasCreate::class,
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
