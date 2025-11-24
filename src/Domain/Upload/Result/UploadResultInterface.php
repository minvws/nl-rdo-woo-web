<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Result;

use Symfony\Component\HttpFoundation\JsonResponse;

interface UploadResultInterface
{
    public function toJsonResponse(): JsonResponse;
}
