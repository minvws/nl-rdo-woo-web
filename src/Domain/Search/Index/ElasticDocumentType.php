<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

enum ElasticDocumentType: string
{
    case WOO_DECISION = 'dossier';
    case WOO_DECISION_DOCUMENT = 'document';
    case COVENANT = 'covenant';

    /**
     * @return self[]
     */
    public static function getMainTypes(): array
    {
        return [
            self::WOO_DECISION,
            self::COVENANT,
        ];
    }

    /**
     * @return self[]
     */
    public static function getSubTypes(): array
    {
        return [
            self::WOO_DECISION_DOCUMENT,
        ];
    }
}
