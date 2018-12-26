<?php

namespace App\Tests\Creator;

use App\Creator\RecoverySecretCreator;
use PHPUnit\Framework\TestCase;

class RecoverySecretCreatorTest extends TestCase
{
    public function testCreate()
    {
        $plainPassword = 'password';
        $recoveryToken = '550e8400-e29b-11d4-a716-446655440000';
        $secret = recoverySecretCreator::create($plainPassword, $recoveryToken);
    }
}
