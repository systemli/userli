<?php

declare(strict_types=1);

namespace App\Form;

use App\Service\SettingsConfigService;
use App\Service\SettingsService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<array<string, mixed>>
 */
class SettingsType extends AbstractType
{
    public function __construct(
        private readonly SettingsConfigService $configService,
        private readonly SettingsService $settingsService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $definitions = $this->configService->getSettings();
        $formData = $builder->getData() ?? [];

        foreach ($definitions as $name => $definition) {
            $this->addSettingField($builder, $name, $definition, $formData);
        }

        $builder->add('save', SubmitType::class, [
            'label' => 'settings.form.save',
        ]);
    }

    private function addSettingField(FormBuilderInterface $builder, string $name, array $definition, array $formData): void
    {
        $type = $definition['type'] ?? 'text';
        $validation = $definition['validation'] ?? [];
        $default = $definition['default'] ?? null;

        // Priority: 1. Form data (from controller), 2. Database value, 3. Definition default
        $currentValue = null;

        if (isset($formData[$name])) {
            // Use value passed from controller
            $currentValue = $formData[$name];
        } else {
            // Get current value from database or use default from definition
            $currentValue = $this->settingsService->get($name);

            // If no value was found, use the default from definition
            if ($currentValue === null) {
                $currentValue = $default;
            }
        }

        $options = [
            'label' => sprintf('settings.form.%s.label', $name),
            'help' => sprintf('settings.form.%s.help', $name),
            'required' => false,
            'data' => $currentValue,
            'constraints' => $this->buildConstraints($validation, $type),
        ];

        // Add field based on type and validation
        $fieldType = $this->determineFieldType($type, $validation);

        if ($fieldType === ChoiceType::class && isset($validation['choices'])) {
            $options['choices'] = array_combine($validation['choices'], $validation['choices']);
        }

        if ($type === 'boolean') {
            $options['required'] = false;
            $options['data'] = (bool) $currentValue;
        }

        $builder->add($name, $fieldType, $options);
    }

    private function determineFieldType(string $type, array $validation): string
    {
        // Choice field for predefined options (check this FIRST)
        if (!empty($validation['choices'])) {
            return ChoiceType::class;
        }

        // Use explicit types from configuration
        return match ($type) {
            'boolean' => CheckboxType::class,
            'integer' => IntegerType::class,
            'float' => NumberType::class,
            'email' => EmailType::class,
            'url' => UrlType::class,
            'password' => PasswordType::class,
            'textarea' => TextareaType::class,
            default => TextType::class,
        };
    }

    private function buildConstraints(array $validation, string $type): array
    {
        $constraints = [];

        // String-like types that should not be empty
        $stringLikeTypes = ['string', 'email', 'url', 'password', 'textarea'];

        // Add NotBlank constraint for all string-like types
        if (in_array($type, $stringLikeTypes, true)) {
            $constraints[] = new Assert\NotBlank();
        }

        // Type-specific constraints
        if ($type === 'integer') {
            $constraints[] = new Assert\Type(['type' => 'integer']);
        }

        if ($type === 'email') {
            $constraints[] = new Assert\Email();
        }

        if ($type === 'url') {
            $constraints[] = new Assert\Url();
        }

        // Length constraints
        if (isset($validation['min_length']) || isset($validation['max_length'])) {
            $lengthOptions = [];
            if (isset($validation['min_length'])) {
                $lengthOptions['min'] = $validation['min_length'];
            }

            if (isset($validation['max_length'])) {
                $lengthOptions['max'] = $validation['max_length'];
            }

            $constraints[] = new Assert\Length($lengthOptions);
        }

        // Range constraints for numbers
        if (isset($validation['min'])) {
            $constraints[] = new Assert\GreaterThanOrEqual([
                'value' => $validation['min'],
            ]);
        }

        if (isset($validation['max'])) {
            $constraints[] = new Assert\LessThanOrEqual([
                'value' => $validation['max'],
            ]);
        }

        // Choice constraints
        if (isset($validation['choices']) && !empty($validation['choices'])) {
            $constraints[] = new Assert\Choice([
                'choices' => $validation['choices'],
            ]);
        }

        return $constraints;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
