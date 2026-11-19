<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ResourceInUseException extends HttpException
{
    public function __construct()
    {
        parent::__construct(Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
