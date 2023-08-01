<?php

declare(strict_types=1);

namespace App\Service\Search\Query\DossierStrategy;

use App\Entity\Dossier;
use App\Service\Search\Model\Config;

class TopLevelDossierStrategy implements DossierStrategyInterface
{
    public function getPath(string $field): string
    {
        return $field;
    }

    public function mustTypeCheck(): bool
    {
        return true;
    }

    public function getMinimumShouldMatch(): int
    {
        return 1;
    }

    public function getStatusValues(Config $config): array
    {
        if (! empty($config->dossierInquiries)) {
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
