<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin\Action;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum DossierAdminAction: string implements TranslatableInterface
{
    case INGEST = 'ingest';
    case INDEX = 'index';
    case VALIDATE_COMPLETION = 'validate_completion';
    case GENERATE_INVENTORY = 'generate_inventory';
    case GENERATE_ARCHIVES = 'generate_archives';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('admin.dossiers.action.label.' . $this->value, locale: $locale);
    }
}
