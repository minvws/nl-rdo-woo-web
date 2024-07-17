<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use App\Domain\Search\Index\ElasticDocumentType;

class SearchResultException extends \RuntimeException
{
    public static function forUnsupportedDocumentType(ElasticDocumentType $documentType): self
    {
        return new self(sprintf(
            'Cannot map result for unsupported document type %s',
            $documentType->value,
        ));
    }
}
