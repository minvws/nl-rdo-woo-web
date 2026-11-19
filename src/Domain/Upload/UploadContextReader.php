<?php

declare(strict_types=1);

namespace Shared\Domain\Upload;

use Shared\Domain\Upload\Exception\UploadException;
use Symfony\Component\Uid\Uuid;

readonly class UploadContextReader
{
    public function getDossierId(UploadEntity $uploadEntity): Uuid
    {
        return $this->getUuid($uploadEntity, 'dossierId');
    }

    public function getDepartmentId(UploadEntity $uploadEntity): Uuid
    {
        return $this->getUuid($uploadEntity, 'departmentId');
    }

    private function getUuid(UploadEntity $uploadEntity, string $key): Uuid
    {
        $value = $uploadEntity->getContext()->getString($key);
        if (! Uuid::isValid($value)) {
            throw UploadException::forInvalidContextUuid($uploadEntity, $key);
        }

        return Uuid::fromString($value);
    }
}
