<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class RequestForAdviceMainDocumentSynchronizer
{
    public function __construct(
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function create(RequestForAdvice $requestForAdvice, RequestForAdviceMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $this->apply($requestForAdvice, RequestForAdviceMainDocumentRequestMapper::create($requestForAdvice, $mainDocumentRequestDto));
    }

    public function update(RequestForAdvice $requestForAdvice, RequestForAdviceMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $mainDocument = $requestForAdvice->getMainDocument() !== null
            ? RequestForAdviceMainDocumentRequestMapper::update($requestForAdvice, $mainDocumentRequestDto)
            : RequestForAdviceMainDocumentRequestMapper::create($requestForAdvice, $mainDocumentRequestDto);

        $this->apply($requestForAdvice, $mainDocument);
    }

    public function delete(RequestForAdvice $requestForAdvice): void
    {
        if ($requestForAdvice->getMainDocument() === null) {
            return;
        }

        $this->messageBus->dispatch(new DeleteMainDocumentCommand($requestForAdvice->getId()));
    }

    private function apply(RequestForAdvice $requestForAdvice, RequestForAdviceMainDocument $mainDocument): void
    {
        $this->dossierMainDocumentValidator->validate($mainDocument);
        $requestForAdvice->setMainDocument($mainDocument);
    }
}
