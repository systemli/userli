<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

final class Twofactor
{
    #[UserPassword(message: 'form.wrong-password')]
    public string $password;
}
