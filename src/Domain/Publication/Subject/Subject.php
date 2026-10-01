<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shared\Doctrine\LandingPageSlugType;
use Shared\Doctrine\LandingPageTitleType;
use Shared\Doctrine\SubjectContentTreeType;
use Shared\Domain\HasId;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubjectRepository::class)]
#[UniqueEntity(fields: ['name', 'organisation'], message: 'subject_already_exists')]
#[UniqueEntity(fields: ['landingPageSlug'], message: 'subject_landing_page_slug_already_exists')]
class Subject implements HasId
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 50)]
    #[Assert\Length(min: 1, max: 50)]
    private string $name;

    #[ORM\ManyToOne(inversedBy: 'subjects')]
    #[ORM\JoinColumn(nullable: false)]
    private Organisation $organisation;

    /** @var Collection<array-key,AbstractDossier> */
    #[ORM\OneToMany(targetEntity: AbstractDossier::class, mappedBy: 'subject')]
    private Collection $dossiers;

    #[ORM\Column(
        type: LandingPageSlugType::NAME,
        length: LandingPageSlug::MAX_LENGTH,
        nullable: true,
        unique: true,
    )]
    private ?LandingPageSlug $landingPageSlug = null;

    #[ORM\Column(
        type: LandingPageTitleType::NAME,
        length: LandingPageTitle::MAX_LENGTH,
        nullable: true,
    )]
    private ?LandingPageTitle $landingPageTitle = null;

    #[ORM\Column(length: 10000, nullable: true)]
    private ?string $landingPageDescription = null;

    #[ORM\Column(type: 'string', length: 255, enumType: SubjectLandingPageStatus::class)]
    private SubjectLandingPageStatus $landingPageStatus = SubjectLandingPageStatus::CONCEPT;

    #[ORM\Column(type: 'uuid', unique: true, nullable: true)]
    private ?Uuid $landingPagePreviewToken = null;

    #[ORM\Column(type: SubjectContentTreeType::NAME, nullable: true, options: ['jsonb' => true])]
    #[Assert\Valid]
    private ?SubjectContentTree $landingPageContentTree = null;

    #[ORM\Column(type: 'string', length: 255, enumType: SubjectContentTreeStatus::class)]
    private SubjectContentTreeStatus $landingPageContentTreeStatus = SubjectContentTreeStatus::CONCEPT;

    public function __construct()
    {
        $this->id = Uuid::v6();
        $this->dossiers = new ArrayCollection();
        $this->landingPagePreviewToken = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getOrganisation(): Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(Organisation $organisation): self
    {
        $this->organisation = $organisation;

        return $this;
    }

    /**
     * @return Collection<AbstractDossier>
     */
    public function getDossiers(): Collection
    {
        return $this->dossiers;
    }

    /**
     * @param Collection<AbstractDossier> $dossiers
     */
    public function setDossiers(Collection $dossiers): void
    {
        $this->dossiers = $dossiers;
    }

    public function setLandingPage(
        LandingPageSlug $slug,
        LandingPageTitle $title,
        string $description,
        SubjectLandingPageStatus $status,
        SubjectContentTree $contentTree,
        SubjectContentTreeStatus $contentTreeStatus,
    ): self {
        $this->landingPageContentTree = $contentTree;
        $this->landingPageContentTreeStatus = $contentTreeStatus;
        $this->landingPageDescription = $description;
        $this->landingPageSlug = $slug;
        $this->landingPageStatus = $status;
        $this->landingPageTitle = $title;

        return $this;
    }

    public function hasPublishedLandingPage(): bool
    {
        return $this->landingPageStatus === SubjectLandingPageStatus::PUBLISHED;
    }

    public function hasPublishedContentTree(): bool
    {
        return $this->landingPageContentTreeStatus === SubjectContentTreeStatus::PUBLISHED;
    }

    public function getLandingPageSlug(): ?LandingPageSlug
    {
        return $this->landingPageSlug;
    }

    public function setLandingPageSlug(LandingPageSlug $slug): self
    {
        $this->landingPageSlug = $slug;

        return $this;
    }

    public function getLandingPageTitle(): ?LandingPageTitle
    {
        return $this->landingPageTitle;
    }

    public function setLandingPageTitle(LandingPageTitle $title): self
    {
        $this->landingPageTitle = $title;

        return $this;
    }

    public function getLandingPageDescription(): ?string
    {
        return $this->landingPageDescription;
    }

    public function setLandingPageDescription(?string $description): self
    {
        $this->landingPageDescription = ($description === null || $description === '')
            ? null
            : $description;

        return $this;
    }

    public function getLandingPageStatus(): SubjectLandingPageStatus
    {
        return $this->landingPageStatus;
    }

    public function setLandingPageStatus(SubjectLandingPageStatus $status): self
    {
        $this->landingPageStatus = $status;

        return $this;
    }

    public function getLandingPagePreviewToken(): ?Uuid
    {
        return $this->landingPagePreviewToken;
    }

    public function getLandingPageContentTree(): ?SubjectContentTree
    {
        return $this->landingPageContentTree;
    }

    public function setLandingPageContentTree(?SubjectContentTree $contentTree): self
    {
        $this->landingPageContentTree = $contentTree;

        return $this;
    }

    public function getLandingPageContentTreeStatus(): SubjectContentTreeStatus
    {
        return $this->landingPageContentTreeStatus;
    }

    public function setLandingPageContentTreeStatus(SubjectContentTreeStatus $status): self
    {
        $this->landingPageContentTreeStatus = $status;

        return $this;
    }

    public function hasLandingPageContent(): bool
    {
        return $this->getLandingPageSlug() !== null
            || $this->getLandingPageTitle() !== null
            || $this->getLandingPageDescription() !== null
            || $this->hasPublishedContentTree()
            || $this->getLandingPageContentTree() !== null;
    }
}
