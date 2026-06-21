<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use PublicationApi\Api\MainDocument\MainDocumentResponseDtoInterface;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

final readonly class RequestForAdviceMainDocumentResponseDto implements MainDocumentResponseDtoInterface
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        public Uuid $id,
        public AttachmentType $type,
        public AttachmentLanguage $language,
        public PlainDate $formalDate,
        public array $grounds,
        public ?string $fileName,
        public UploadStatus $uploadStatus,
        #[SerializedName('_links')]
        public LinkCollection $halLinks,
    ) {
    }

    #[Ignore]
    public static function getAllowedTypes(): array
    {
        return RequestForAdviceMainDocument::getAllowedTypes();
    }
}
