<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum;

enum DocumentFileUploadStatus: string
{
    case PENDING = 'pending';
    case UPLOADED = 'uploaded';
    case FAILED = 'failed';
    case PROCESSED = 'processed';

    /**
     * @codeCoverageIgnore
     *
     * @return list<self>
     */
    public static function finalStatuses(): array
    {
        return [
            self::FAILED,
            self::PROCESSED,
        ];
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isUploaded(): bool
    {
        return $this === self::UPLOADED;
    }

    public function isProcessed(): bool
    {
        return $this === self::PROCESSED;
    }
}
