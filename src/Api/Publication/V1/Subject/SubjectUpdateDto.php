<?php

declare(strict_types=1);

namespace App\Api\Publication\V1\Subject;

use Symfony\Component\Validator\Constraints as Assert;

class SubjectUpdateDto
{
    #[Assert\NotBlank(normalizer: 'trim')]
    public string $name;
}
