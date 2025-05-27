<?php

declare(strict_types=1);

namespace App\Domain\Uploader\Result;

use Symfony\Component\HttpFoundation\JsonResponse;

interface UploadResultInterface
{
    public function toJsonResponse(): JsonResponse;
}
