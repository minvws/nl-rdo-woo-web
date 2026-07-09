<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic;

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Doctrine\PlainDateType;
use Shared\Doctrine\TimestampableTrait;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NoticeNotPublicRepository::class)]
#[ORM\Table(name: 'notice_not_public')]
class NoticeNotPublic
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\OneToOne(targetEntity: AbstractDossier::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AbstractDossier $dossier;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $documentName;

    #[ORM\Column(type: PlainDateType::NAME)]
    private PlainDate $formalDate;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $grounds;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $explanation;

    /**
     * @param list<string> $grounds
     */
    public function __construct(
        Uuid $id,
        AbstractDossier $dossier,
        ?string $documentName,
        PlainDate $formalDate,
        array $grounds,
        ?string $explanation,
    ) {
        $this->id = $id;
        $this->dossier = $dossier;
        $this->documentName = $documentName;
        $this->formalDate = $formalDate;
        $this->grounds = $grounds;
        $this->explanation = $explanation;

        $this->createdAt = new CarbonImmutable();
        $this->updatedAt = new CarbonImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getDossier(): AbstractDossier
    {
        return $this->dossier;
    }

    public function getDocumentName(): ?string
    {
        return $this->documentName;
    }

    public function setDocumentName(?string $documentName): void
    {
        $this->documentName = $documentName;
    }

    public function getFormalDate(): PlainDate
    {
        return $this->formalDate;
    }

    public function setFormalDate(PlainDate $formalDate): void
    {
        $this->formalDate = $formalDate;
    }

    /**
     * @return list<string>
     */
    public function getGrounds(): array
    {
        return $this->grounds;
    }

    /**
     * @param list<string> $grounds
     */
    public function setGrounds(array $grounds): void
    {
        $this->grounds = $grounds;
    }

    public function getExplanation(): ?string
    {
        return $this->explanation;
    }

    public function setExplanation(?string $explanation): void
    {
        $this->explanation = $explanation;
    }
}
