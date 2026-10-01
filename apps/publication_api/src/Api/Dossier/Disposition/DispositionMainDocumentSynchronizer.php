<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class DispositionMainDocumentSynchronizer
{
    public function __construct(
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function create(Disposition $disposition, DispositionMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $this->apply($disposition, DispositionMainDocumentRequestMapper::create($disposition, $mainDocumentRequestDto));
    }

    public function update(Disposition $disposition, DispositionMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $mainDocument = $disposition->getMainDocument() !== null
            ? DispositionMainDocumentRequestMapper::update($disposition, $mainDocumentRequestDto)
            : DispositionMainDocumentRequestMapper::create($disposition, $mainDocumentRequestDto);

        $this->apply($disposition, $mainDocument);
    }

    public function delete(Disposition $disposition): void
    {
        if ($disposition->getMainDocument() === null) {
            return;
        }

        $this->messageBus->dispatch(new DeleteMainDocumentCommand($disposition->getId()));
    }

    private function apply(Disposition $disposition, DispositionMainDocument $mainDocument): void
    {
        $this->dossierMainDocumentValidator->validate($mainDocument);
        $disposition->setMainDocument($mainDocument);
    }
}
