<?php

use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\CodeQuality\Rector\ClassMethod\ActionSuffixRemoverRector;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);
    $rectorConfig->symfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml');
    $rectorConfig->importNames();
    $rectorConfig->phpVersion(PhpVersion::PHP_80);

    $rectorConfig->skip([
        // SonataAdminBundle CRUDController needs the suffix for actions
        ActionSuffixRemoverRector::class => [
            __DIR__ . '/src/Controller/AliasCRUDController.php',
            __DIR__ . '/src/Controller/UserCRUDController.php',
        ],
    ]);

    $rectorConfig->sets([
        SetList::PHP_80,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);
};
