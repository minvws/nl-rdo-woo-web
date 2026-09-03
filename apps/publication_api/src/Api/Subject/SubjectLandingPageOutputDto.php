<?php

declare(strict_types=1);

namespace PublicationApi\Api\Subject;

use ApiPlatform\Metadata\ApiProperty;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;

final readonly class SubjectLandingPageOutputDto
{
    /**
     * @param list<array<string, mixed>> $contentTree
     */
    public function __construct(
        public SubjectLandingPageStatus $status,
        public string $slug,
        public string $title,
        public string $description,
        #[ApiProperty(jsonSchemaContext: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'required' => ['title', 'body', 'children'],
                'properties' => [
                    'title' => ['type' => 'string'],
                    'body' => ['type' => 'string'],
                    'children' => ['type' => 'array'],
                ],
            ],
        ])]
        public array $contentTree,
        public ?string $previewUrl,
    ) {
    }
}
