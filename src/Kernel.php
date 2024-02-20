<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function getCacheDir(): string
    {
        $projectDir = parent::getProjectDir();
        if ('/vagrant' === $projectDir && in_array($this->environment, ['dev', 'test'])) {
            $projectDir = '/dev/shm/userli';
        }

        return $projectDir.'/var/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        $projectDir = parent::getProjectDir();
        if ('/vagrant' === $projectDir && in_array($this->environment, ['dev', 'test'])) {
            $projectDir = '/dev/shm/userli';
        }

        return $projectDir.'/var/log';
    }
}
