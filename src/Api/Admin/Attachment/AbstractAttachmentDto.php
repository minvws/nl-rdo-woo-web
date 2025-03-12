<?php

declare(strict_types=1);

namespace App\Api\Admin\Attachment;

use ApiPlatform\Metadata\ApiProperty;
use App\Api\Admin\Dossier\DossierReferenceDto;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
abstract readonly class AbstractAttachmentDto
{
    /**
     * @param array<array-key,string> $grounds
     */
    final public function __construct(
        #[ApiProperty(writable: false, identifier: true, genId: false)]
        public string $id,
        public DossierReferenceDto $dossier,
        public string $name,
        #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        #[ApiProperty(
            openapiContext: [
                'type' => 'string',
                'format' => 'date',
            ],
            jsonSchemaContext: [
                'type' => 'string',
                'format' => 'date',
            ]
        )]
        public \DateTimeImmutable $formalDate,
        public string $type,
        public string $mimeType,
        public int $size,
        public string $internalReference,
        public string $language,
        #[ApiProperty(
            openapiContext: [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
            jsonSchemaContext: [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ]
        )]
        public array $grounds,
        public string $withdrawUrl,
    ) {
    }
}
