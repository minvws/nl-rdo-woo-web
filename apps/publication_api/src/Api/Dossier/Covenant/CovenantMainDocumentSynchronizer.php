<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class CovenantMainDocumentSynchronizer
{
    public function __construct(
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function create(Covenant $covenant, CovenantMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $this->apply($covenant, CovenantMainDocumentRequestMapper::create($covenant, $mainDocumentRequestDto));
    }

    public function update(Covenant $covenant, CovenantMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $mainDocument = $covenant->getMainDocument() !== null
            ? CovenantMainDocumentRequestMapper::update($covenant, $mainDocumentRequestDto)
            : CovenantMainDocumentRequestMapper::create($covenant, $mainDocumentRequestDto);

        $this->apply($covenant, $mainDocument);
    }

    public function delete(Covenant $covenant): void
    {
        if ($covenant->getMainDocument() === null) {
            return;
        }

        $this->messageBus->dispatch(new DeleteMainDocumentCommand($covenant->getId()));
    }

    private function apply(Covenant $covenant, CovenantMainDocument $mainDocument): void
    {
        $this->dossierMainDocumentValidator->validate($mainDocument);
        $covenant->setMainDocument($mainDocument);
    }
}
