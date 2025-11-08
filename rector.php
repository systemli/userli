<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
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
    ])
    ->withPreparedSets(codingStyle: true, privatization: true, symfonyCodeQuality: true)
    ->withAttributesSets(symfony: true, doctrine: true)
    ->withComposerBased(doctrine: true, symfony: true);
