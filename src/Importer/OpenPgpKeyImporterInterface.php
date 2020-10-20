<?php

namespace App\Importer;

use App\Entity\OpenPgpKey;

interface OpenPgpKeyImporterInterface
{
    public static function import(string $email, string $data): OpenPgpKey;
}
