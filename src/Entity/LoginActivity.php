<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LoginActivityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LoginActivityRepository::class)]
class LoginActivity
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\ManyToOne(inversedBy: 'loginActivities')]
    #[ORM\JoinColumn(nullable: false)]
    private User $account;

    #[ORM\Column]
    private \DateTimeImmutable $loginAt;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAccount(): User
    {
        return $this->account;
    }

    public function setAccount(User $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getLoginAt(): \DateTimeImmutable
    {
        return $this->loginAt;
    }

    public function setLoginAt(\DateTimeImmutable $loginAt): static
    {
        $this->loginAt = $loginAt;

        return $this;
    }
}
