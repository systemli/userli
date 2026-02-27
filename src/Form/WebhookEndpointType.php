<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Domain;
use App\Enum\WebhookEvent;
use App\Form\Model\WebhookEndpointModel;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<WebhookEndpointModel>
 */
final class WebhookEndpointType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $events = WebhookEvent::all();
        $choices = array_combine($events, $events);
        $builder
            ->add('url', TextType::class)
            ->add('secret', TextType::class)
            ->add('events', ChoiceType::class, [
                'choices' => $choices,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('domains', EntityType::class, [
                'class' => Domain::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
                'autocomplete' => true,
            ])
            ->add('enabled', CheckboxType::class, [
                'required' => false,
            ])
            ->add('submit', SubmitType::class);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WebhookEndpointModel::class,
        ]);
    }
}
