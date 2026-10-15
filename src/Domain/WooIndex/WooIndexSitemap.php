<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex;

use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping as ORM;
use Shared\Doctrine\TimestampableTrait;
use Shared\ValueObject\WooUuid;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WooIndexSitemapRepository::class)]
#[ORM\HasLifecycleCallbacks]
class WooIndexSitemap
{
    use TimestampableTrait;

    #[ORM\Column(length: 255, enumType: WooIndexSitemapStatus::class)]
    private WooIndexSitemapStatus $status = WooIndexSitemapStatus::PROCESSING;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'uuid')]
        private Uuid $id = new WooUuid(),
    ) {
        $this->createdAt = new CarbonImmutable();
        $this->updatedAt = new CarbonImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getStatus(): ?WooIndexSitemapStatus
    {
        return $this->status;
    }

    public function setStatus(WooIndexSitemapStatus $status): static
    {
        $this->status = $status;

        return $this;
    }
}
