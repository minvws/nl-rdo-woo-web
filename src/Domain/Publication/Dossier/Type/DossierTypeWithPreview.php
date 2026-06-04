<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type;

use Shared\ValueObject\PlainDate;

interface DossierTypeWithPreview
{
    public function getPreviewDate(): ?PlainDate;

    public function setPreviewDate(?PlainDate $previewDate): static;

    public function hasFuturePreviewDate(): bool;
}
