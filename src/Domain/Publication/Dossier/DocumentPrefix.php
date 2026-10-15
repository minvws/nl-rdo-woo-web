<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier;

use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\HasId;
use Shared\Domain\Organisation\Organisation;
use Shared\ValueObject\WooUuid;
use Symfony\Component\Uid\Uuid;

use function strtoupper;

/**
 * Keeps the legacy document_prefix table represented in the Doctrine schema.
 */
#[ORM\Entity]
#[ORM\Table(name: 'document_prefix')]
class DocumentPrefix implements HasId
{
    #[ORM\Column(length: 255, unique: true, nullable: false)]
    private string $prefix;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'organisation_id', referencedColumnName: 'id', nullable: false)]
    private Organisation $organisation;

    #[ORM\Column(options: ['default' => false])]
    private bool $archived = false;

    public function __construct(
        string $prefix,
        #[ORM\Id]
        #[ORM\Column(type: 'uuid')]
        private Uuid $id = new WooUuid(),
    ) {
        $this->prefix = strtoupper($prefix);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPrefix(): string
    {
        return strtoupper($this->prefix);
    }

    public function setPrefix(string $prefix): static
    {
        $this->prefix = strtoupper($prefix);

        return $this;
    }

    public function getOrganisation(): Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(Organisation $organisation): static
    {
        $this->organisation = $organisation;

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function archive(): void
    {
        $this->archived = true;
    }
}
