<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument;

use App\Domain\Publication\AttachmentAndMainDocumentEntityTrait;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use App\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use App\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Uploader\UploadGroupId;
use Doctrine\ORM\Mapping as ORM;
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
])]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractMainDocument implements EntityWithFileInfo
{
    use AttachmentAndMainDocumentEntityTrait;

    #[ORM\OneToOne(targetEntity: AbstractDossier::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    protected AbstractDossier $dossier;

    public function getDossier(): AbstractDossier&EntityWithMainDocument
    {
        Assert::isInstanceOf($this->dossier, EntityWithMainDocument::class);

        return $this->dossier;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getUploadGroupId(): UploadGroupId
    {
        return UploadGroupId::MAIN_DOCUMENTS;
    }
}
