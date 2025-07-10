<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier;

use App\Domain\Organisation\Organisation;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

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

    public function getPrefix(): string
    {
        return strtoupper($this->prefix);
    }

    public function setPrefix(string $prefix): static
    {
        if (isset($this->prefix)) {
            throw new \RuntimeException('The prefix can only be set on creation, never updated');
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
