<?php

declare(strict_types=1);

namespace App\Entity;

use App\ValueObject\TranslatableMessage;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

interface EntityWithBatchDownload
{
    public function getId(): ?Uuid;

    /**
     * @return Collection|Document[]
     */
    public function getDocuments(): Collection;

    public function isAvailableForBatchDownload(): bool;

    public function getDownloadFilePrefix(): TranslatableMessage;
}
