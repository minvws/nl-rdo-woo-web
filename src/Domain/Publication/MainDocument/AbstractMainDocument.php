<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\Publication\AttachmentAndMainDocumentEntityTrait;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTable;
use Shared\Service\Uploader\UploadGroupId;
use Webmozart\Assert\Assert;

/**
 * @template TDossier of AbstractDossier&EntityWithMainDocument
 *
 * @property TDossier $dossier
 */
#[ORM\Entity(repositoryClass: MainDocumentRepository::class)]
#[ORM\Table(name: 'main_document')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'entity_type', type: 'string')]
#[ORM\DiscriminatorMap([
    'covenant_main_document' => CovenantMainDocument::class,
    'annual_report_main_document' => AnnualReportMainDocument::class,
    'investigation_report_main_document' => InvestigationReportMainDocument::class,
    'disposition_main_document' => DispositionMainDocument::class,
    'complaint_judgement_main_document' => ComplaintJudgementMainDocument::class,
    'woo_decision_main_document' => WooDecisionMainDocument::class,
    'other_publication_main_document' => OtherPublicationMainDocument::class,
    'advice_main_document' => AdviceMainDocument::class,
    'request_for_advice_main_document' => RequestForAdviceMainDocument::class,
    'draft_decision_main_document' => DraftDecisionMainDocument::class,
])]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractMainDocument implements EntityWithFileInfo
{
    use AttachmentAndMainDocumentEntityTrait;

    #[ORM\OneToOne(targetEntity: AbstractDossier::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    protected AbstractDossier $dossier;

    /** @var array{columns: list<string>, rows: list<list<string>>}|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $spreadsheetTable = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $spreadsheetTableTruncated = false;

    public function getDossier(): AbstractDossier&EntityWithMainDocument
    {
        Assert::isInstanceOf($this->dossier, EntityWithMainDocument::class);

        return $this->dossier;
    }

    public function getSpreadsheetTable(): ?SpreadsheetTable
    {
        return $this->spreadsheetTable === null ? null : SpreadsheetTable::fromArray($this->spreadsheetTable);
    }

    public function setSpreadsheetTable(?SpreadsheetTable $spreadsheetTable): void
    {
        $this->spreadsheetTable = $spreadsheetTable?->toArray();
    }

    public function isSpreadsheetTableTruncated(): bool
    {
        return $this->spreadsheetTableTruncated;
    }

    public function setSpreadsheetTableTruncated(bool $spreadsheetTableTruncated): void
    {
        $this->spreadsheetTableTruncated = $spreadsheetTableTruncated;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getUploadGroupId(): UploadGroupId
    {
        return UploadGroupId::MAIN_DOCUMENTS;
    }
}
