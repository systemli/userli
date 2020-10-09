<?php

namespace App\Form\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class OpenPgpKeyFile
{
    /**
     * @var UploadedFile
     */
    public $key;
}
