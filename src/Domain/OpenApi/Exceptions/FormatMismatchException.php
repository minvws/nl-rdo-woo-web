<?php

declare(strict_types=1);

namespace Shared\Domain\OpenApi\Exceptions;

use League\OpenAPIValidation\Schema\Exception\FormatMismatch;

class FormatMismatchException extends ValidatonException
{
    private ?string $format = null;

    public static function fromFormatMismatch(FormatMismatch $formatMismatch): self
    {
        $formatMismatchException = self::fromThrowable($formatMismatch);
        $formatMismatchException->format = $formatMismatch->format();

        return $formatMismatchException;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }
}
