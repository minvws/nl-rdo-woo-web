<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class InvestigationReportMainDocumentSynchronizer
{
    public function __construct(
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function create(InvestigationReport $investigationReport, InvestigationReportMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $this->apply($investigationReport, InvestigationReportMainDocumentRequestMapper::create($investigationReport, $mainDocumentRequestDto));
    }

    public function update(InvestigationReport $investigationReport, InvestigationReportMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $mainDocument = $investigationReport->getMainDocument() !== null
            ? InvestigationReportMainDocumentRequestMapper::update($investigationReport, $mainDocumentRequestDto)
            : InvestigationReportMainDocumentRequestMapper::create($investigationReport, $mainDocumentRequestDto);

        $this->apply($investigationReport, $mainDocument);
    }

    public function delete(InvestigationReport $investigationReport): void
    {
        if ($investigationReport->getMainDocument() === null) {
            return;
        }

        $this->messageBus->dispatch(new DeleteMainDocumentCommand($investigationReport->getId()));
    }

    private function apply(InvestigationReport $investigationReport, InvestigationReportMainDocument $mainDocument): void
    {
        $this->dossierMainDocumentValidator->validate($mainDocument);
        $investigationReport->setMainDocument($mainDocument);
    }
}
