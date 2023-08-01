<?php

declare(strict_types=1);

namespace App\Service\Search\Query\DossierStrategy;

use App\Entity\Dossier;
use App\Service\Search\Model\Config;

class NestedDossierStrategy implements DossierStrategyInterface
{
    public function getPath(string $field): string
    {
        return 'dossiers.' . $field;
    }

    public function mustTypeCheck(): bool
    {
        return false;
    }

    public function getMinimumShouldMatch(): int
    {
        return 0;
    }

    public function getStatusValues(Config $config): array
    {
        if (! empty($config->dossierInquiries) || ! empty($config->documentInquiries)) {
            return [
                Dossier::STATUS_PUBLISHED,
                Dossier::STATUS_PREVIEW,
            ];
        }

        return [
            Dossier::STATUS_PUBLISHED,
        ];
    }
}
