<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strtolower;

/**
 * @phpstan-type AttachmentLanguageArray array{
 *   type: string,
 *   value: string,
 *   label: string,
 * }
 */
enum AttachmentLanguage: string implements TranslatableInterface
{
    public const TRANS_DOMAIN = 'attachment';

    case DUTCH = 'Dutch';
    case ENGLISH = 'English';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(
            strtolower($this->name),
            domain: self::TRANS_DOMAIN,
            locale: $locale,
        );
    }

    /**
     * @return array<string,string>
     *
     * @phpstan-return AttachmentLanguageArray
     */
    public function toArray(TranslatorInterface $translator, ?string $locale = null): array
    {
        return [
            'type' => 'AttachmentLanguage',
            'value' => $this->value,
            'label' => $this->trans($translator, $locale),
        ];
    }
}
