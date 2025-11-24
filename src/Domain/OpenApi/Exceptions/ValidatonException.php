<?php

declare(strict_types=1);

namespace Shared\Domain\OpenApi\Exceptions;

class ValidatonException extends \Exception
{
    final public function __construct(string $message, int $code, \Throwable $previous)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function fromThrowable(\Throwable $throwable): static
    {
        return new static($throwable->getMessage(), $throwable->getCode(), $throwable);
    }
}
