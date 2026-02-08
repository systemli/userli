<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Symfony\CodeQuality\Rector\Class_\ControllerMethodInjectionToConstructorRector;
use Rector\Symfony\CodeQuality\Rector\ClassMethod\ActionSuffixRemoverRector;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/src'])
    ->withSymfonyContainerXml(__DIR__.'/var/cache/dev/App_KernelDevDebugContainer.xml')
    ->withImportNames(removeUnusedImports: true)
    ->withSkip([
        // SonataAdminBundle CRUDController needs the suffix for actions
        ActionSuffixRemoverRector::class => [
            __DIR__.'/src/Controller/AliasCRUDController.php',
            __DIR__.'/src/Controller/UserCRUDController.php',
        ],
        // SonataAdmin batch actions receive ProxyQueryInterface as a runtime
        // parameter via request attributes, not as a service.
        ControllerMethodInjectionToConstructorRector::class => [
            __DIR__.'/src/Controller/UserCRUDController.php',
            __DIR__.'/src/Controller/AliasCRUDController.php',
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
