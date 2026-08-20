<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\MainDocument\MainDocumentResponseDtoInterface;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

final readonly class DraftDecisionMainDocumentResponseDto implements MainDocumentResponseDtoInterface
{
    public function __construct(
        public Uuid $id,
        public AttachmentType $type,
        public AttachmentLanguage $language,
        public PlainDate $formalDate,
        public ?string $fileName,
        public UploadStatus $uploadStatus,
        #[SerializedName('_links')]
        public LinkCollection $halLinks,
    ) {
    }

    #[Ignore]
    public static function getAllowedTypes(): array
    {
        return DraftDecisionMainDocument::getAllowedTypes();
    }
}
