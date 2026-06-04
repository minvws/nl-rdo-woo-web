<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

trait TimestampableTrait
{
    #[ORM\Column(nullable: false)]
    protected DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: false)]
    protected DateTimeImmutable $updatedAt;

    protected bool $hasUpdatedAtOverride = false;

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        if (! $this->hasUpdatedAtOverride) {
            $this->updatedAt = new CarbonImmutable();
        }
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        $this->hasUpdatedAtOverride = true;

        return $this;
    }

    public function hasCreatedAt(): bool
    {
        return isset($this->createdAt);
    }
}
