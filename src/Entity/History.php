<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\HistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: HistoryRepository::class)]
#[ORM\Index(columns: ['type', 'identifier'])]
class History
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $type;

    #[ORM\Column(type: 'uuid')]
    private Uuid $identifier;

    #[ORM\Column]
    private \DateTimeImmutable $createdDt;

    #[ORM\Column(length: 255)]
    private string $contextKey;

    /** @var mixed[] */
    #[ORM\Column]
    private array $context = [];

    #[ORM\Column(length: 255, options: ['default' => 'both'])]
    private string $site;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getIdentifier(): Uuid
    {
        return $this->identifier;
    }

    public function setIdentifier(Uuid $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getCreatedDt(): \DateTimeImmutable
    {
        return $this->createdDt;
    }

    public function setCreatedDt(\DateTimeImmutable $createdDt): static
    {
        $this->createdDt = $createdDt;

        return $this;
    }

    public function getContextKey(): string
    {
        return $this->contextKey;
    }

    public function setContextKey(string $contextKey): static
    {
        $this->contextKey = $contextKey;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param mixed[] $context
     *
     * @return $this
     */
    public function setContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getSite(): string
    {
        return $this->site;
    }

    public function setSite(string $site): static
    {
        $this->site = $site;

        return $this;
    }
}
