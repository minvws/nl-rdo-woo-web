<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\ViewModel;

use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Search\Result\SubType\SubTypeViewModelInterface;

readonly class MainDocument implements SubTypeViewModelInterface
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
        public bool $withdrawn = false,
    ) {
    }
}
