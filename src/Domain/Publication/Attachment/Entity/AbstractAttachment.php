<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Entity;

use App\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use App\Domain\Publication\Attachment\Exception\AttachmentWithdrawException;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\AttachmentAndMainDocumentEntityTrait;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Uploader\UploadGroupId;
use Doctrine\DBAL\Types\Types;
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
    'other_publication_attachment' => OtherPublicationAttachment::class,
    'advice_attachment' => AdviceAttachment::class,
])]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractAttachment implements EntityWithFileInfo
{
    use AttachmentAndMainDocumentEntityTrait;

    #[ORM\ManyToOne(targetEntity: AbstractDossier::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    protected AbstractDossier $dossier;

    #[ORM\Column]
    private bool $withdrawn = false;

    #[ORM\Column(length: 255, nullable: true, enumType: AttachmentWithdrawReason::class)]
    private ?AttachmentWithdrawReason $withdrawReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $withdrawExplanation = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $withdrawDate = null;

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

    public function canWithdraw(): bool
    {
        return $this->dossier->getStatus()->isPubliclyAvailableOrScheduled()
            && $this->fileInfo->isUploaded()
            && ! $this->isWithdrawn();
    }

    public function isWithdrawn(): bool
    {
        return $this->withdrawn;
    }

    public function getWithdrawReason(): ?AttachmentWithdrawReason
    {
        return $this->withdrawReason;
    }

    public function getWithdrawExplanation(): ?string
    {
        return $this->withdrawExplanation;
    }

    public function getWithdrawDate(): ?\DateTimeImmutable
    {
        return $this->withdrawDate;
    }

    public function withdraw(AttachmentWithdrawReason $reason, string $explanation): void
    {
        if (! $this->canWithdraw()) {
            throw AttachmentWithdrawException::forCannotWithdraw();
        }

        $this->withdrawn = true;
        $this->withdrawReason = $reason;
        $this->withdrawExplanation = $explanation;
        $this->withdrawDate = new \DateTimeImmutable();

        $this->fileInfo->removeFileProperties();
    }
}
