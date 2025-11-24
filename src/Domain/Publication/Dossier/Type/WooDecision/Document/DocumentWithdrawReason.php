<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum DocumentWithdrawReason: string implements TranslatableInterface
{
    case DATA_IN_DOCUMENT = 'data_in_document';
    case DATA_IN_FILE = 'data_in_file';
    case SUSPENDED_DOCUMENT = 'suspended_document';
    case UNREADABLE_DOCUMENT = 'unreadable_document';
    case INCORRECT_ATTACHMENT = 'incorrect_attachment';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(
            id: $this->getTranslationKey(),
            locale: $locale,
        );
    }

    public function getTranslationKey(): string
    {
        return sprintf('global.document.withdraw.reason.%s', $this->value);
    }
}
