<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Prefix;

use Symfony\Component\Validator\Constraints as Assert;

class PrefixCreateDto
{
    #[Assert\NotBlank(normalizer: 'trim')]
    public string $prefix;
}
