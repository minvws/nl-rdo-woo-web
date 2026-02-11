<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier;

use Doctrine\ORM\Mapping as ORM;
use RuntimeException;
use Shared\Domain\Organisation\Organisation;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

use function strtoupper;

#[ORM\Entity(repositoryClass: DocumentPrefixRepository::class)]
#[UniqueEntity('prefix')]
class DocumentPrefix
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 255, unique: true, nullable: false)]
    private string $prefix;

    #[ORM\ManyToOne(inversedBy: 'documentPrefixes')]
    #[ORM\JoinColumn(nullable: false)]
    private Organisation $organisation;

    #[ORM\Column(options: ['default' => false])]
    private bool $archived = false;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function issetPrefix(): bool
    {
        return isset($this->prefix);
    }

    public function getPrefix(): string
    {
        return strtoupper($this->prefix);
    }

    public function setPrefix(string $prefix): static
    {
        if (isset($this->prefix)) {
            throw new RuntimeException('The prefix can only be set on creation, never updated');
        }

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
