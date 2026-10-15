<?php

declare(strict_types=1);

namespace Shared\Service\Security\LoginActivity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Shared\Service\Security\User;
use Shared\ValueObject\WooUuid;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LoginActivityRepository::class)]
class LoginActivity
{
    #[ORM\ManyToOne(inversedBy: 'loginActivities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private User $account;

    #[ORM\Column]
    private DateTimeImmutable $loginAt;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'uuid')]
        private Uuid $id = new WooUuid(),
    ) {
    }

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

    public function getLoginAt(): DateTimeImmutable
    {
        return $this->loginAt;
    }

    public function setLoginAt(DateTimeImmutable $loginAt): static
    {
        $this->loginAt = $loginAt;

        return $this;
    }
}
