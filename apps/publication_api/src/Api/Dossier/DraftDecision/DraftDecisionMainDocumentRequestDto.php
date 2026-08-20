<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\MainDocument\MainDocumentRequestDtoInterface;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Validator\AllowedFileExtension;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

class DraftDecisionMainDocumentRequestDto implements MainDocumentRequestDtoInterface
{
    public function __construct(
        #[AllowedFileExtension(UploadGroupId::MAIN_DOCUMENTS)]
        public FileName $fileName,
        public PlainDate $formalDate,
        public AttachmentLanguage $language,
        #[Assert\Choice(callback: [self::class, 'getAllowedTypes'])]
        public AttachmentType $type,
    ) {
    }

    public static function getAllowedTypes(): array
    {
        return DraftDecisionMainDocument::getAllowedTypes();
    }
}
