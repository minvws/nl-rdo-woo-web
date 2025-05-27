<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\ViewModel;

use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use App\Domain\Search\Result\SubType\SubTypeViewModelInterface;
use App\SourceType;

readonly class Attachment implements SubTypeViewModelInterface
{
    /**
     * @param list<string> $grounds
     *
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function __construct(
        public string $id,
        public ?string $name,
        public string $formalDate,
        public AttachmentType $type,
        public ?string $mimeType,
        public ?SourceType $sourceType,
        public int $size,
        public string $internalReference,
        public AttachmentLanguage $language,
        public array $grounds,
        public string $downloadUrl,
        public string $detailsUrl,
        public int $pageCount,
        public bool $withdrawn,
        public ?AttachmentWithdrawReason $withdrawReason,
        public ?\DateTimeImmutable $withdrawDate,
    ) {
    }
}
