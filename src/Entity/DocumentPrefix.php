<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DocumentPrefixRepository;
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

    #[ORM\Column(length: 255, nullable: false)]
    private string $description;

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
