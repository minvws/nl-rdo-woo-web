<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use ApiPlatform\Metadata\ApiProperty;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoInterface;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

final readonly class CovenantMainDocumentResponseDto implements MainDocumentResponseDtoInterface
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        #[ApiProperty(description: 'The unique identifier of the main document.')]
        public Uuid $id,
        #[ApiProperty(description: 'The document type of the main document.')]
        public AttachmentType $type,
        #[ApiProperty(description: 'The language of the main document.')]
        public AttachmentLanguage $language,
        #[ApiProperty(description: 'The formal date of the main document (format YYYY-MM-DD).')]
        public PlainDate $formalDate,
        #[ApiProperty(description: 'The Woo exemption grounds applied to the main document.')]
        public array $grounds,
        #[ApiProperty(description: 'The file name of the uploaded main document, if available.')]
        public ?string $fileName,
        #[ApiProperty(description: 'The status of the main document\'s upload process.')]
        public UploadStatus $uploadStatus,
        #[SerializedName('_links')]
        #[ApiProperty(description: 'HAL links associated with the main document: `upload` (where to upload the file) '
            . 'and, once the dossier is published, `public` (the main document\'s public page) and `file` (a direct '
            . 'download link).')]
        public LinkCollection $halLinks,
    ) {
    }

    #[Ignore]
    public static function getAllowedTypes(): array
    {
        return CovenantMainDocument::getAllowedTypes();
    }
}
