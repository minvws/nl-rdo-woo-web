<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use Shared\Domain\Publication\Attachment\Exception\AttachmentWithdrawException;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\AttachmentAndMainDocumentEntityTrait;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Uploader\UploadGroupId;
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
    'request_for_advice_attachment' => RequestForAdviceAttachment::class,
])]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractAttachment implements EntityWithFileInfo
{
    use AttachmentAndMainDocumentEntityTrait;

    public const int MAX_ATTACHMENTS_PER_DOSSIER = 50;
    public const int WITHDRAW_EXPLANATION_MIN_LENGTH = 1;
    public const int WITHDRAW_EXPLANATION_MAX_LENGTH = 1000;

    #[ORM\ManyToOne(targetEntity: AbstractDossier::class)]
    #[ORM\JoinColumn(name: 'dossier_id', referencedColumnName: 'id', nullable: false, onDelete: 'cascade')]
    protected AbstractDossier $dossier;

    #[ORM\Column(options: ['default' => false])]
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

        Assert::lengthBetween(
            $explanation,
            self::WITHDRAW_EXPLANATION_MIN_LENGTH,
            self::WITHDRAW_EXPLANATION_MAX_LENGTH,
        );

        $this->withdrawn = true;
        $this->withdrawReason = $reason;
        $this->withdrawExplanation = $explanation;
        $this->withdrawDate = new \DateTimeImmutable();

        $this->fileInfo->removeFileProperties();
    }
}
