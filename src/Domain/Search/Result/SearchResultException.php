<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result;

use RuntimeException;
use Shared\Domain\Search\Index\ElasticDocumentType;

use function sprintf;

class SearchResultException extends RuntimeException
{
    public static function forUnsupportedDocumentType(ElasticDocumentType $documentType): self
    {
        return new self(sprintf(
            'Cannot map result for unsupported document type %s',
            $documentType->value,
        ));
    }
}
