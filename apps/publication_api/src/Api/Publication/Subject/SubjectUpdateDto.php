<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Subject;

use Symfony\Component\Validator\Constraints as Assert;

class SubjectUpdateDto
{
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim')]
        public string $name,
    ) {
    }
}
