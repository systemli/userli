<?php

use Rector\Doctrine\Set\DoctrineSetList;
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
    $rectorConfig->phpVersion(\Rector\ValueObject\PhpVersion::PHP_81);

    $rectorConfig->skip([
        // SonataAdminBundle CRUDController needs the suffix for actions
        ActionSuffixRemoverRector::class => [
            __DIR__ . '/src/Controller/AliasCRUDController.php',
            __DIR__ . '/src/Controller/UserCRUDController.php',
        ],
    ]);

    $rectorConfig->sets([
        SetList::PHP_81,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
};
