<?php

declare(strict_types=1);

namespace PublicationApi\Api\Prefix;

use Symfony\Component\Validator\Constraints as Assert;

class PrefixCreateDto
{
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim')]
        public string $prefix,
    ) {
    }
}
