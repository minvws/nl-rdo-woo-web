<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum AttachmentWithdrawReason: string implements TranslatableInterface
{
    case UNRELATED = 'unrelated';
    case INCOMPLETE = 'incomplete';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(
            id: $this->getTranslationKey(),
            locale: $locale,
        );
    }

    public function getTranslationKey(): string
    {
        return sprintf('global.attachment.withdraw.reason.%s', $this->value);
    }
}
