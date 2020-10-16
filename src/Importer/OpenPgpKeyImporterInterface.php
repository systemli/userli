<?php

namespace App\Importer;

use App\Model\OpenPGPKeyInfo;

interface OpenPgpKeyImporterInterface
{
    public static function import(string $email, string $data): OpenPGPKeyInfo;
}
