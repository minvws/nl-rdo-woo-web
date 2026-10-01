<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class AdviceMainDocumentSynchronizer
{
    public function __construct(
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function create(Advice $advice, AdviceMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $this->apply($advice, AdviceMainDocumentRequestMapper::create($advice, $mainDocumentRequestDto));
    }

    public function update(Advice $advice, AdviceMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $mainDocument = $advice->getMainDocument() !== null
            ? AdviceMainDocumentRequestMapper::update($advice, $mainDocumentRequestDto)
            : AdviceMainDocumentRequestMapper::create($advice, $mainDocumentRequestDto);

        $this->apply($advice, $mainDocument);
    }

    public function delete(Advice $advice): void
    {
        if ($advice->getMainDocument() === null) {
            return;
        }

        $this->messageBus->dispatch(new DeleteMainDocumentCommand($advice->getId()));
    }

    private function apply(Advice $advice, AdviceMainDocument $mainDocument): void
    {
        $this->dossierMainDocumentValidator->validate($mainDocument);
        $advice->setMainDocument($mainDocument);
    }
}
