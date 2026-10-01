<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use PublicationApi\Api\Dossier\DossierMainDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class OtherPublicationMainDocumentSynchronizer
{
    public function __construct(
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function create(OtherPublication $otherPublication, OtherPublicationMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $this->apply($otherPublication, OtherPublicationMainDocumentRequestMapper::create($otherPublication, $mainDocumentRequestDto));
    }

    public function update(OtherPublication $otherPublication, OtherPublicationMainDocumentRequestDto $mainDocumentRequestDto): void
    {
        $mainDocument = $otherPublication->getMainDocument() !== null
            ? OtherPublicationMainDocumentRequestMapper::update($otherPublication, $mainDocumentRequestDto)
            : OtherPublicationMainDocumentRequestMapper::create($otherPublication, $mainDocumentRequestDto);

        $this->apply($otherPublication, $mainDocument);
    }

    public function delete(OtherPublication $otherPublication): void
    {
        if ($otherPublication->getMainDocument() === null) {
            return;
        }

        $this->messageBus->dispatch(new DeleteMainDocumentCommand($otherPublication->getId()));
    }

    private function apply(OtherPublication $otherPublication, OtherPublicationMainDocument $mainDocument): void
    {
        $this->dossierMainDocumentValidator->validate($mainDocument);
        $otherPublication->setMainDocument($mainDocument);
    }
}
