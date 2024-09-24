<?php

declare(strict_types=1);

namespace App\Domain\Search\Query;

use App\Domain\Search\Index\ElasticDocumentType;
use Symfony\Component\HttpFoundation\ParameterBag;

enum SearchType: string
{
    case DOSSIER = ElasticDocumentType::WOO_DECISION->value;
    case DOCUMENT = ElasticDocumentType::WOO_DECISION_DOCUMENT->value;
    case ALL = 'all';

    public const DEFAULT = self::ALL;

    public static function fromParameterBag(ParameterBag $parameterBag): self
    {
        $type = self::tryFrom($parameterBag->getString('type', ''));
        if ($type === null) {
            $type = self::DEFAULT;
        }

        return $type;
    }

    public function isDossier(): bool
    {
        return $this === self::DOSSIER;
    }

    public function isDocument(): bool
    {
        return $this === self::DOCUMENT;
    }

    public function isAll(): bool
    {
        return $this === self::ALL;
    }

    public function isNotAll(): bool
    {
        return ! $this->isAll();
    }
}
