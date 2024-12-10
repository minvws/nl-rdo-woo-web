<?php

declare(strict_types=1);

namespace App\Domain\Publication;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

interface EntityWithBatchDownload
{
    public function getId(): ?Uuid;

    /**
     * @return Collection<array-key,Document>
     */
    public function getDocuments(): Collection;

    public function isAvailableForBatchDownload(): bool;

    public function getBatchFileName(): string;
}
