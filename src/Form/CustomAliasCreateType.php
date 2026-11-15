<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\DataTransformer\TextToEmailTransformer;
use App\Form\Model\AliasCreate;
use Override;
use Symfony\Component\Form\AbstractType;
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
            ->add($builder->create('alias', TextType::class, ['label' => 'form.new-custom-alias'])
                ->addViewTransformer(new TextToEmailTransformer($options['domain'])))
            ->add('submit', SubmitType::class, ['label' => 'form.create-custom-alias']);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
            $data = $event->getData();

            if (!is_array($data) || !isset($data['alias']) || !is_string($data['alias'])) {
                return;
            }

            $data['alias'] = strtolower($data['alias']);
            $event->setData($data);
        });
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AliasCreate::class,
        ]);
        $resolver->setRequired('domain');
        $resolver->setAllowedTypes('domain', 'string');
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
