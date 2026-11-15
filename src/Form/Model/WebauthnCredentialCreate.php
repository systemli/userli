<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Empty form fields arrive as null, the constraints report them.
 */
final class WebauthnCredentialCreate
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $name = '';

    #[UserPassword(message: 'form.wrong-password')]
    private string $password = '';

    // Browser response of navigator.credentials.create()
    #[Assert\NotBlank(message: 'form.webauthn-failed')]
    private string $response = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name ?? '';
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password ?? '';
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function setResponse(?string $response): void
    {
        $this->response = $response ?? '';
    }
}
