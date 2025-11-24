<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Subject;

use Symfony\Component\Validator\Constraints as Assert;

class SubjectCreateDto
{
    #[Assert\NotBlank(normalizer: 'trim')]
    public string $name;
}
