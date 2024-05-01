<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @phpstan-import-type AttachmentLanguageArray from AttachmentLanguage
 */
final readonly class AttachmentLanguageFactory
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @phpstan-return array<int,AttachmentLanguageArray>
     *
     * @return array<int,array<string,string>>
     */
    public function makeAsArray(): array
    {
        return (new ArrayCollection(AttachmentLanguage::cases()))
            ->map(fn (AttachmentLanguage $case): array => $case->toArray($this->translator))
            ->toArray();
    }
}
