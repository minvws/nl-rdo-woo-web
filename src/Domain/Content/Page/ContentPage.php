<?php

declare(strict_types=1);

namespace App\Domain\Content\Page;

use App\Doctrine\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContentPageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ContentPage
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(length: 20)]
    #[Assert\Sequentially([
        new Assert\Length(min: 2, max: 20),
        new Assert\Regex(pattern: '/^[a-z0-9-]+$/i', message: 'use_only_letters_numbers_and_dashes'),
    ])]
    private string $slug;

    #[ORM\Column(length: 100, nullable: false)]
    #[Assert\Length(min: 1, max: 100)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: false)]
    #[Assert\NotBlank]
    private string $content;

    public function __construct(string $slug, string $title, string $content)
    {
        $this->slug = $slug;
        $this->title = $title;
        $this->content = $content;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
