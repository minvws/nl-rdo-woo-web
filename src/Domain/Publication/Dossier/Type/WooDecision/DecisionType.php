<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum DecisionType: string implements TranslatableInterface
{
    case ALREADY_PUBLIC = 'already_public';
    case PUBLIC = 'public';
    case PARTIAL_PUBLIC = 'partial_public';
    case NOT_PUBLIC = 'not_public';
    case NOTHING_FOUND = 'nothing_found';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('dossier.type.woo-decision.decision-type.' . $this->value, locale: $locale);
    }
}
