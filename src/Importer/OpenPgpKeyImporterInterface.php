<?php

declare(strict_types=1);

namespace App\Importer;

use App\Entity\OpenPgpKey;

interface OpenPgpKeyImporterInterface
{
    public static function import(string $email, string $data): OpenPgpKey;
}
