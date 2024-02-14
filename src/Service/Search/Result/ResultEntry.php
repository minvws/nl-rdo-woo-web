<?php

declare(strict_types=1);

namespace App\Service\Search\Result;

interface ResultEntry
{
    // Document types
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_DOSSIER = 'dossier';

    public function getType(): string;

    /** @return string[] */
    public function getHighlights(): array;
}
