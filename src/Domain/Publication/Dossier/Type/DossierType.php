<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum DossierType: string implements TranslatableInterface
{
    case COVENANT = 'covenant';
    case WOO_DECISION = 'woo-decision';

    public function isCovenant(): bool
    {
        return $this === self::COVENANT;
    }

    public function isWooDecision(): bool
    {
        return $this === self::WOO_DECISION;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('dossier.type.' . $this->value, locale: $locale);
    }
}
