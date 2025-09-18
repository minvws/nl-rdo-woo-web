<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum;

enum DocumentFileSetStatus: string
{
    case OPEN_FOR_UPLOADS = 'open_for_uploads';
    case PROCESSING_UPLOADS = 'processing_uploads';
    case NEEDS_CONFIRMATION = 'needs_confirmation';
    case CONFIRMED = 'confirmed';
    case REJECTED = 'rejected';
    case MAX_SIZE_EXCEEDED = 'max_size_exceeded';
    case PROCESSING_UPDATES = 'processing_updates';
    case COMPLETED = 'completed';
    case NO_CHANGES = 'no_changes';

    /**
     * @codeCoverageIgnore
     *
     * @return list<string>
     */
    public static function getFinalStatusValues(): array
    {
        return [
            self::COMPLETED->value,
            self::REJECTED->value,
            self::NO_CHANGES->value,
        ];
    }

    public function isOpenForUploads(): bool
    {
        return $this === self::OPEN_FOR_UPLOADS;
    }

    public function isProcessingUploads(): bool
    {
        return $this === self::PROCESSING_UPLOADS;
    }

    public function needsConfirmation(): bool
    {
        return $this === self::NEEDS_CONFIRMATION;
    }

    public function isConfirmed(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function isMaxSizeExceeded(): bool
    {
        return $this === self::MAX_SIZE_EXCEEDED;
    }
}
