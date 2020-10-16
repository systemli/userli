<?php

namespace App\Importer;

use App\Model\OpenPGPKey;

interface OpenPgpKeyImporterInterface
{
    public static function import(string $email, string $data): OpenPGPKey;
}
