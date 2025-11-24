<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type;

interface DossierTypeWithPreview
{
    public function getPreviewDate(): ?\DateTimeImmutable;

    public function setPreviewDate(?\DateTimeImmutable $previewDate): static;

    public function hasFuturePreviewDate(): bool;
}
