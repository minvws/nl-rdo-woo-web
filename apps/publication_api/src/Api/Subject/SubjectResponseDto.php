<?php

declare(strict_types=1);

namespace PublicationApi\Api\Subject;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Uid\Uuid;

final readonly class SubjectResponseDto
{
    public function __construct(
        #[ApiProperty(description: 'The unique identifier of the subject.')]
        public Uuid $id,
        #[ApiProperty(description: 'The name of the subject.')]
        public string $name,
    ) {
    }
}
