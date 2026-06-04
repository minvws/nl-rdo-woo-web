<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Enum;

use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;
use function strcmp;
use function usort;

/**
 * @phpstan-import-type AttachmentLanguageArray from AttachmentLanguage
 */
readonly class AttachmentLanguageFactory
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @return array<int, AttachmentLanguageArray>
     */
    public function makeAsArray(): array
    {
        $cases = AttachmentLanguage::cases();
        usort($cases, $this->compare(...));

        return array_map(fn (AttachmentLanguage $case) => $case->toArray($this->translator), $cases);
    }

    private function compare(AttachmentLanguage $a, AttachmentLanguage $b): int
    {
        return match (true) {
            $a === AttachmentLanguage::NLD => -1,
            $b === AttachmentLanguage::NLD => 1,
            default => strcmp($a->trans($this->translator), $b->trans($this->translator)),
        };
    }
}
