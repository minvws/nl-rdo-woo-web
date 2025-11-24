<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum PublicationReason: string implements TranslatableInterface
{
    case WOB_REQUEST = 'wob_request';
    case WOO_REQUEST = 'woo_request';
    // Not in use for now
    // case WOO_ACTIVE = 'woo_active';

    public static function getDefault(): self
    {
        return self::WOO_REQUEST;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('dossier.type.woo-decision.publication-reason.' . $this->value, locale: $locale);
    }
}
