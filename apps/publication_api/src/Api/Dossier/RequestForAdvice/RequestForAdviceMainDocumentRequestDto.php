<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use ApiPlatform\Metadata\ApiProperty;
use PublicationApi\Api\MainDocument\MainDocumentRequestDtoInterface;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Validator\AllowedFileExtension;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

class RequestForAdviceMainDocumentRequestDto implements MainDocumentRequestDtoInterface
{
    /**
     * @param list<Ground> $grounds
     */
    public function __construct(
        #[AllowedFileExtension(UploadGroupId::MAIN_DOCUMENTS)]
        #[ApiProperty(description: 'The file name of the main document, including extension.')]
        public FileName $fileName,
        #[ApiProperty(description: 'The formal date of the main document, for example the date it was adopted or signed (format YYYY-MM-DD).')]
        public PlainDate $formalDate,
        #[ApiProperty(description: 'The language of the main document.')]
        public AttachmentLanguage $language,
        #[Assert\Choice(callback: [self::class, 'getAllowedTypes'])]
        #[ApiProperty(description: 'The document type of the main document, limited to the types allowed for a request for advice.')]
        public AttachmentType $type,
        #[ApiProperty(description: 'The Woo exemption grounds applied to the main document.')]
        public array $grounds = [],
    ) {
    }

    public static function getAllowedTypes(): array
    {
        return RequestForAdviceMainDocument::getAllowedTypes();
    }
}
