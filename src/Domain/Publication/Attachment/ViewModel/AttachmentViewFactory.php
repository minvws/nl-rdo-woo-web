<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\ViewModel;

use App\Citation;
use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\Exception\AttachmentRuntimeException;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use App\Entity\DecisionAttachment;
use App\Enum\ApplicationMode;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
readonly class AttachmentViewFactory
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * @return array<array-key,Attachment>
     */
    public function makeCollection(
        AbstractDossier $dossier,
        ApplicationMode $mode = ApplicationMode::PUBLIC
    ): array {
        if (! $dossier instanceof EntityWithAttachments) {
            return [];
        }

        return $dossier
            ->getAttachments()
            ->map(fn (AbstractAttachment $entity): Attachment => $this->make($dossier, $entity, $mode))
            ->toArray();
    }

    public function make(
        AbstractDossier&EntityWithAttachments $dossier,
        AbstractAttachment $attachment,
        ApplicationMode $mode = ApplicationMode::PUBLIC,
    ): Attachment {
        return match (true) {
            $attachment instanceof DecisionAttachment => $this->makeDecisionAttachment($dossier, $attachment, $mode),
            $attachment instanceof CovenantAttachment => $this->makeCovenantAttachment($dossier, $attachment, $mode),
            $attachment instanceof AnnualReportAttachment => $this->makeAnnualReportAttachment($dossier, $attachment, $mode),
            $attachment instanceof InvestigationReportAttachment => $this->makeInvestigationReportAttachment($dossier, $attachment, $mode),
            $attachment instanceof DispositionAttachment => $this->makeDispositionAttachment($dossier, $attachment, $mode),
            default => throw AttachmentRuntimeException::unknownAttachmentType($attachment::class),
        };
    }

    private function makeDecisionAttachment(
        AbstractDossier&EntityWithAttachments $dossier,
        AbstractAttachment $attachment,
        ApplicationMode $mode,
    ): Attachment {
        $downloadRouteName = $mode === ApplicationMode::ADMIN
            ? 'app_admin_woodecision_decisionattachment_download'
            : 'app_woodecision_decisionattachment_download';

        $parameters = [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
            'attachmentId' => $attachment->getId(),
        ];

        return $this->doMake($attachment, $downloadRouteName, 'app_woodecision_decisionattachment_detail', $parameters);
    }

    private function makeCovenantAttachment(
        AbstractDossier&EntityWithAttachments $dossier,
        AbstractAttachment $attachment,
        ApplicationMode $mode,
    ): Attachment {
        $downloadRouteName = $mode === ApplicationMode::ADMIN
            ? 'app_admin_covenant_covenantattachment_download'
            : 'app_covenant_covenantattachment_download';

        $parameters = [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
            'attachmentId' => $attachment->getId(),
        ];

        return $this->doMake($attachment, $downloadRouteName, 'app_covenant_covenantattachment_detail', $parameters);
    }

    private function makeAnnualReportAttachment(
        AbstractDossier&EntityWithAttachments $dossier,
        AbstractAttachment $attachment,
        ApplicationMode $mode,
    ): Attachment {
        $downloadRouteName = $mode === ApplicationMode::ADMIN
            ? 'app_admin_annualreport_attachment_download'
            : 'app_annualreport_attachment_download';

        $parameters = [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
            'attachmentId' => $attachment->getId(),
        ];

        return $this->doMake($attachment, $downloadRouteName, 'app_annualreport_attachment_detail', $parameters);
    }

    private function makeInvestigationReportAttachment(
        AbstractDossier&EntityWithAttachments $dossier,
        AbstractAttachment $attachment,
        ApplicationMode $mode,
    ): Attachment {
        $downloadRouteName = $mode === ApplicationMode::ADMIN
            ? 'app_admin_investigationreport_attachment_download'
            : 'app_investigationreport_attachment_download';

        $parameters = [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
            'attachmentId' => $attachment->getId(),
        ];

        return $this->doMake($attachment, $downloadRouteName, 'app_investigationreport_attachment_detail', $parameters);
    }

    private function makeDispositionAttachment(
        AbstractDossier&EntityWithAttachments $dossier,
        AbstractAttachment $attachment,
        ApplicationMode $mode,
    ): Attachment {
        $downloadRouteName = $mode === ApplicationMode::ADMIN
            ? 'app_admin_disposition_attachment_download'
            : 'app_disposition_attachment_download';

        $parameters = [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
            'attachmentId' => $attachment->getId(),
        ];

        return $this->doMake($attachment, $downloadRouteName, 'app_disposition_attachment_detail', $parameters);
    }

    /**
     * @param array<array-key,string|Uuid> $parameters
     */
    private function doMake(
        AbstractAttachment $attachment,
        string $downloadRouteName,
        string $detailRouteName,
        array $parameters,
    ): Attachment {
        return new Attachment(
            id: $attachment->getId()->toRfc4122(),
            name: $attachment->getFileInfo()->getName(),
            formalDate: $attachment->getFormalDate()->format('Y-m-d'),
            type: $attachment->getType(),
            mimeType: $attachment->getFileInfo()->getMimetype(),
            sourceType: $attachment->getFileInfo()->getSourceType(),
            size: $attachment->getFileInfo()->getSize(),
            internalReference: $attachment->getInternalReference(),
            language: $attachment->getLanguage(),
            grounds: Citation::sortWooCitations($attachment->getGrounds()),
            downloadUrl: $this->generateUrl($downloadRouteName, $parameters),
            detailsUrl: $this->generateUrl($detailRouteName, $parameters),
            pageCount: $attachment->getFileInfo()->getPageCount() ?? 0,
        );
    }

    /**
     * @param array<array-key,string|Uuid> $parameters
     */
    private function generateUrl(string $name, array $parameters): string
    {
        return $this->urlGenerator->generate($name, $parameters);
    }
}
