<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\MainDocument\MainDocumentRequestDtoInterface;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Citation;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Validator\AllowedFileExtension;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

class ComplaintJudgementMainDocumentRequestDto implements MainDocumentRequestDtoInterface
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        #[AllowedFileExtension(UploadGroupId::MAIN_DOCUMENTS)]
        public FileName $fileName,
        public PlainDate $formalDate,
        public AttachmentLanguage $language,
        #[Assert\Choice(callback: [self::class, 'getAllowedTypes'])]
        public AttachmentType $type,
        #[Assert\All([
            new Assert\Choice(choices: Citation::ALL_GROUND_KEYS),
        ])]
        public array $grounds = [],
    ) {
    }

    public static function getAllowedTypes(): array
    {
        return ComplaintJudgementMainDocument::getAllowedTypes();
    }
}
