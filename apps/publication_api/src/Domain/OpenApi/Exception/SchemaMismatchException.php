<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Exception;

use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use Webmozart\Assert\Assert;

use function implode;

class SchemaMismatchException extends ValidationException
{
    private ?string $breadCrumb = null;

    public static function fromSchemaMismatch(SchemaMismatch $schemaMismatch): self
    {
        $schemaMismatchException = static::fromThrowable($schemaMismatch);

        $breadCrumb = $schemaMismatch->dataBreadCrumb();
        if ($breadCrumb !== null) {
            $chain = $breadCrumb->buildChain();
            Assert::allNullOrScalar($chain);

            $schemaMismatchException->breadCrumb = implode('.', $chain);
        }

        return $schemaMismatchException;
    }

    public function getBreadCrumb(): ?string
    {
        return $this->breadCrumb;
    }
}
