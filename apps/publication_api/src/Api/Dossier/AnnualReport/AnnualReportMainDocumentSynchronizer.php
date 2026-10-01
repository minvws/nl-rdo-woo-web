<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class AnnualReportMainDocumentSynchronizer
{
    public function __construct(
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function create(AnnualReport $annualReport, AnnualReportMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $this->apply($annualReport, AnnualReportMainDocumentRequestMapper::create($annualReport, $mainDocumentRequestDto));
    }

    public function update(AnnualReport $annualReport, AnnualReportMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $mainDocument = $annualReport->getMainDocument() !== null
            ? AnnualReportMainDocumentRequestMapper::update($annualReport, $mainDocumentRequestDto)
            : AnnualReportMainDocumentRequestMapper::create($annualReport, $mainDocumentRequestDto);

        $this->apply($annualReport, $mainDocument);
    }

    public function delete(AnnualReport $annualReport): void
    {
        if ($annualReport->getMainDocument() === null) {
            return;
        }

        $this->messageBus->dispatch(new DeleteMainDocumentCommand($annualReport->getId()));
    }

    private function apply(AnnualReport $annualReport, AnnualReportMainDocument $mainDocument): void
    {
        $this->dossierMainDocumentValidator->validate($mainDocument);
        $annualReport->setMainDocument($mainDocument);
    }
}
