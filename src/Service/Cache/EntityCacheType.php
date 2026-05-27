<?php

declare(strict_types=1);

namespace App\Service\Cache;

enum EntityCacheType: string
{
    case USER = 'user';
    case ALIAS = 'alias';
    case DOMAIN = 'domain';
    case OPENPGP_KEY = 'openpgp_key';
}
