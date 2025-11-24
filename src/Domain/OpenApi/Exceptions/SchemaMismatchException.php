<?php

declare(strict_types=1);

namespace Shared\Domain\OpenApi\Exceptions;

use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;

class SchemaMismatchException extends ValidatonException
{
    private ?string $breadCrumb = null;

    public static function fromSchemaMismatch(SchemaMismatch $schemaMismatch): self
    {
        $schemaMismatchException = static::fromThrowable($schemaMismatch);
        if ($schemaMismatch->dataBreadCrumb() !== null) {
            $schemaMismatchException->breadCrumb = \implode('.', $schemaMismatch->dataBreadCrumb()->buildChain());
        }

        return $schemaMismatchException;
    }

    public function getBreadCrumb(): ?string
    {
        return $this->breadCrumb;
    }
}
