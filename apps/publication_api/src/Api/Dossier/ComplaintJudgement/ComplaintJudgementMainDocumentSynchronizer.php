<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class ComplaintJudgementMainDocumentSynchronizer
{
    public function __construct(
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function create(ComplaintJudgement $complaintJudgement, ComplaintJudgementMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $this->apply($complaintJudgement, ComplaintJudgementMainDocumentRequestMapper::create($complaintJudgement, $mainDocumentRequestDto));
    }

    public function update(ComplaintJudgement $complaintJudgement, ComplaintJudgementMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $mainDocument = $complaintJudgement->getMainDocument() !== null
            ? ComplaintJudgementMainDocumentRequestMapper::update($complaintJudgement, $mainDocumentRequestDto)
            : ComplaintJudgementMainDocumentRequestMapper::create($complaintJudgement, $mainDocumentRequestDto);

        $this->apply($complaintJudgement, $mainDocument);
    }

    public function delete(ComplaintJudgement $complaintJudgement): void
    {
        if ($complaintJudgement->getMainDocument() === null) {
            return;
        }

        $this->messageBus->dispatch(new DeleteMainDocumentCommand($complaintJudgement->getId()));
    }

    private function apply(ComplaintJudgement $complaintJudgement, ComplaintJudgementMainDocument $mainDocument): void
    {
        $this->dossierMainDocumentValidator->validate($mainDocument);
        $complaintJudgement->setMainDocument($mainDocument);
    }
}
