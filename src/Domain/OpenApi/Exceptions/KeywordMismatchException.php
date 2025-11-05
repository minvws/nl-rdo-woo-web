<?php

declare(strict_types=1);

namespace App\Domain\OpenApi\Exceptions;

use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;

class KeywordMismatchException extends ValidatonException
{
    private ?string $keyword = null;

    public static function fromKeywordMismatch(KeywordMismatch $keywordMismatch): self
    {
        $keywordMismatchException = self::fromThrowable($keywordMismatch);
        $keywordMismatchException->keyword = $keywordMismatch->keyword();

        return $keywordMismatchException;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }
}
