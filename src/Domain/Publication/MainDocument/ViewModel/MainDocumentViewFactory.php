<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument\ViewModel;

use App\Citation;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportDocument;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementDocument;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionDocument;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Publication\MainDocument\Exception\MainDocumentRuntimeException;
use App\Enum\ApplicationMode;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
readonly class MainDocumentViewFactory
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function make(
        AbstractDossier&EntityWithMainDocument $dossier,
        AbstractMainDocument $mainDocument,
        ApplicationMode $mode = ApplicationMode::PUBLIC,
    ): MainDocument {
        return match (true) {
            $mainDocument instanceof CovenantDocument => $this->makeCovenantDocument($dossier, $mainDocument, $mode),
            $mainDocument instanceof AnnualReportDocument => $this->makeAnnualReportDocument($dossier, $mainDocument, $mode),
            $mainDocument instanceof InvestigationReportDocument => $this->makeInvestigationReportDocument($dossier, $mainDocument, $mode),
            $mainDocument instanceof DispositionDocument => $this->makeDispositionDocument($dossier, $mainDocument, $mode),
            $mainDocument instanceof ComplaintJudgementDocument => $this->makeComplaintJudgementDocument($dossier, $mainDocument, $mode),
            default => throw MainDocumentRuntimeException::unknownMainDocumentType($mainDocument::class),
        };
    }

    private function makeCovenantDocument(
        AbstractDossier&EntityWithMainDocument $dossier,
        AbstractMainDocument $mainDocument,
        ApplicationMode $mode,
    ): MainDocument {
        $downloadRouteName = $mode === ApplicationMode::ADMIN
            ? 'app_admin_covenant_covenantdocument_download'
            : 'app_covenant_covenantdocument_download';

        $parameters = [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ];

        return $this->doMake($mainDocument, $downloadRouteName, 'app_covenant_covenantdocument_detail', $parameters);
    }

    private function makeAnnualReportDocument(
        AbstractDossier&EntityWithMainDocument $dossier,
        AbstractMainDocument $mainDocument,
        ApplicationMode $mode,
    ): MainDocument {
        $downloadRouteName = $mode === ApplicationMode::ADMIN
            ? 'app_admin_annualreport_document_download'
            : 'app_annualreport_document_download';

        $parameters = [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ];

        return $this->doMake($mainDocument, $downloadRouteName, 'app_annualreport_document_detail', $parameters);
    }

    private function makeInvestigationReportDocument(
        AbstractDossier&EntityWithMainDocument $dossier,
        AbstractMainDocument $mainDocument,
        ApplicationMode $mode,
    ): MainDocument {
        $downloadRouteName = $mode === ApplicationMode::ADMIN
            ? 'app_admin_investigationreport_document_download'
            : 'app_investigationreport_document_download';

        $parameters = [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ];

        return $this->doMake($mainDocument, $downloadRouteName, 'app_investigationreport_document_detail', $parameters);
    }

    private function makeDispositionDocument(
        AbstractDossier&EntityWithMainDocument $dossier,
        AbstractMainDocument $mainDocument,
        ApplicationMode $mode,
    ): MainDocument {
        $downloadRouteName = $mode === ApplicationMode::ADMIN
            ? 'app_admin_disposition_document_download'
            : 'app_disposition_document_download';

        $parameters = [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ];

        return $this->doMake($mainDocument, $downloadRouteName, 'app_disposition_document_detail', $parameters);
    }

    private function makeComplaintJudgementDocument(
        AbstractDossier&EntityWithMainDocument $dossier,
        AbstractMainDocument $mainDocument,
        ApplicationMode $mode,
    ): MainDocument {
        $downloadRouteName = $mode === ApplicationMode::ADMIN
            ? 'app_admin_complaintjudgement_document_download'
            : 'app_complaintjudgement_document_download';

        $parameters = [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ];

        return $this->doMake($mainDocument, $downloadRouteName, 'app_complaintjudgement_document_detail', $parameters);
    }

    /**
     * @param array<array-key,string|Uuid> $parameters
     */
    private function doMake(
        AbstractMainDocument $mainDocument,
        string $downloadRouteName,
        string $detailRouteName,
        array $parameters,
    ): MainDocument {
        return new MainDocument(
            id: $mainDocument->getId()->toRfc4122(),
            name: $mainDocument->getFileInfo()->getName(),
            formalDate: $mainDocument->getFormalDate()->format('Y-m-d'),
            type: $mainDocument->getType(),
            mimeType: $mainDocument->getFileInfo()->getMimetype(),
            sourceType: $mainDocument->getFileInfo()->getSourceType(),
            size: $mainDocument->getFileInfo()->getSize(),
            internalReference: $mainDocument->getInternalReference(),
            language: $mainDocument->getLanguage(),
            grounds: Citation::sortWooCitations($mainDocument->getGrounds()),
            downloadUrl: $this->generateUrl($downloadRouteName, $parameters),
            detailsUrl: $this->generateUrl($detailRouteName, $parameters),
            pageCount: $mainDocument->getFileInfo()->getPageCount() ?? 0,
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
