<?php

namespace App\Dto;

use App\Validator\Constraints\EmailAddress;
use App\Validator\Constraints\EmailLength;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints\AliasCreate;

class AliasDto
{
	#[Assert\NotBlank]
	#[Assert\Email(mode: 'strict')]
	#[EmailAddress(groups: ['unique'])]
	#[EmailLength(minLength: 3, maxLength: 24)]
	#[AliasCreate(
		custom_alias_limit: 3,
		random_alias_limit: 100
	)]
	public readonly ?string $localpart;
}
