<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Symfony\CodeQuality\Rector\Class_\EventSubscriberToAttributeRector;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/src'])
    ->withSymfonyContainerXml(__DIR__.'/var/cache/dev/App_KernelDevDebugContainer.xml')
    ->withImportNames(removeUnusedImports: true)
    ->withSkip([
        // Form event subscribers are registered via FormBuilder::addEventSubscriber(),
        // which requires EventSubscriberInterface.
        EventSubscriberToAttributeRector::class => [
            __DIR__.'/src/Form/OpenPgpKeyType.php',
        ],
        // Doctrine entities: keep ORM attributes above class properties for readability,
        // rather than inlining them into constructor promotion.
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__.'/src/Entity',
        ],
    ])
    ->withPreparedSets(codingStyle: true, privatization: true, symfonyCodeQuality: true)
    ->withAttributesSets(symfony: true, doctrine: true)
    ->withComposerBased(doctrine: true, symfony: true)
    ->withPhpSets(php84: true);
