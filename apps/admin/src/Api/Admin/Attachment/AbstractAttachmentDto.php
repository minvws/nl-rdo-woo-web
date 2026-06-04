<?php

declare(strict_types=1);

namespace Admin\Api\Admin\Attachment;

use Admin\Api\Admin\Dossier\DossierReferenceDto;
use ApiPlatform\Metadata\ApiProperty;
use Shared\ValueObject\PlainDate;

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
        public PlainDate $formalDate,
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
            ],
        )]
        public array $grounds,
        public string $withdrawUrl,
    ) {
    }
}
