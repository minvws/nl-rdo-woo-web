<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WorkerStatsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkerStatsRepository::class)]
class WorkerStats
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 100)]
    private string $section;

    #[ORM\Column]
    private int $count;

    // Bigints are converted to string,.. because of PHP's int size limitations on 32bit machines.
    #[ORM\Column(type: 'bigint')]
    private string $duration;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function setSection(string $section): self
    {
        $this->section = $section;

        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getDuration(): int
    {
        return (int) $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = strval($duration);

        return $this;
    }
}
