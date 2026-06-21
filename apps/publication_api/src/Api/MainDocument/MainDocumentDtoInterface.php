<?php

declare(strict_types=1);

namespace PublicationApi\Api\MainDocument;

use Shared\Domain\Publication\Attachment\Enum\AttachmentType;

/**
 * Common contract for per-dossier MainDocument DTOs (both request and response).
 * Exposes the dossier-specific set of allowed AttachmentTypes so that:
 *   - Symfony validation can reference self::getAllowedTypes() via #[Assert\Choice(callback: ...)]
 *   - MainDocumentAttachmentTypePropertyMetadataFactory can restrict the OpenAPI enum without reflection.
 */
interface MainDocumentDtoInterface
{
    /**
     * Returns the AttachmentTypes allowed for this dossier type's main document.
     *
     * @return list<AttachmentType>
     */
    public static function getAllowedTypes(): array;
}
