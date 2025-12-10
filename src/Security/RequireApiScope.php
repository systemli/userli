<?php

declare(strict_types=1);

namespace App\Security;

use App\Enum\ApiScope;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class RequireApiScope
{
    public function __construct(
        public ApiScope $scope,
    ) {
    }
}
