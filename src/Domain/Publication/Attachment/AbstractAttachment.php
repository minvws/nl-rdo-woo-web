<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use App\Domain\Publication\AttachmentAndMainDocumentEntityTrait;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecisionAttachment;
use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Uploader\UploadGroupId;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Entity(repositoryClass: AttachmentRepository::class)]
#[ORM\Table(name: 'attachment')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'entity_type', type: 'string')]
#[ORM\DiscriminatorMap([
    'covenant_attachment' => CovenantAttachment::class,
    'annual_report_attachment' => AnnualReportAttachment::class,
    'decision_attachment' => WooDecisionAttachment::class,
    'investigation_report_attachment' => InvestigationReportAttachment::class,
    'disposition_attachment' => DispositionAttachment::class,
])]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractAttachment implements EntityWithFileInfo
{
    use AttachmentAndMainDocumentEntityTrait;

    #[ORM\ManyToOne(targetEntity: AbstractDossier::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    protected AbstractDossier $dossier;

    public function getDossier(): AbstractDossier&EntityWithAttachments
    {
        Assert::isInstanceOf($this->dossier, EntityWithAttachments::class);

        return $this->dossier;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getUploadGroupId(): UploadGroupId
    {
        return UploadGroupId::ATTACHMENTS;
    }
}
