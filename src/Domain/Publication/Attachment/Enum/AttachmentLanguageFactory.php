<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Enum;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @phpstan-import-type AttachmentLanguageArray from AttachmentLanguage
 */
readonly class AttachmentLanguageFactory
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @return array<int,array<string,string>>
     *
     * @phpstan-return array<int,AttachmentLanguageArray>
     */
    public function makeAsArray(): array
    {
        return (new ArrayCollection(AttachmentLanguage::cases()))
            ->map(fn (AttachmentLanguage $case): array => $case->toArray($this->translator))
            ->toArray();
    }
}
