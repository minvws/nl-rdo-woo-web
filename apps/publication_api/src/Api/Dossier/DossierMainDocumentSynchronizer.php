<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use PublicationApi\Api\MainDocument\MainDocumentRequestDtoInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class DossierMainDocumentSynchronizer
{
    public function __construct(
        private DossierMainDocumentValidator $dossierMainDocumentValidator,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @template TDossier of AbstractDossier&EntityWithMainDocument
     * @template TMainDocumentRequestDto of MainDocumentRequestDtoInterface
     *
     * @param TDossier $dossier
     * @param TMainDocumentRequestDto $mainDocumentRequestDto
     * @param MainDocumentRequestMapperInterface<TDossier,TMainDocumentRequestDto> $mainDocumentRequestMapper
     */
    public function create(
        AbstractDossier&EntityWithMainDocument $dossier,
        MainDocumentRequestDtoInterface $mainDocumentRequestDto,
        MainDocumentRequestMapperInterface $mainDocumentRequestMapper,
    ): void {
        $this->apply($dossier, $mainDocumentRequestMapper->create($dossier, $mainDocumentRequestDto));
    }

    /**
     * @template TDossier of AbstractDossier&EntityWithMainDocument
     * @template TMainDocumentRequestDto of MainDocumentRequestDtoInterface
     *
     * @param TDossier $dossier
     * @param TMainDocumentRequestDto $mainDocumentRequestDto
     * @param MainDocumentRequestMapperInterface<TDossier,TMainDocumentRequestDto> $mainDocumentRequestMapper
     */
    public function update(
        AbstractDossier&EntityWithMainDocument $dossier,
        MainDocumentRequestDtoInterface $mainDocumentRequestDto,
        MainDocumentRequestMapperInterface $mainDocumentRequestMapper,
    ): void {
        if (! $dossier->getMainDocument() instanceof AbstractMainDocument) {
            $this->apply($dossier, $mainDocumentRequestMapper->create($dossier, $mainDocumentRequestDto));

            return;
        }

        $this->apply($dossier, $mainDocumentRequestMapper->update($dossier, $mainDocumentRequestDto));
    }

    public function delete(AbstractDossier&EntityWithMainDocument $dossier): void
    {
        if (! $dossier->getMainDocument() instanceof AbstractMainDocument) {
            return;
        }

        $this->dossierMainDocumentValidator->validateDelete($dossier);
        $this->messageBus->dispatch(new DeleteMainDocumentCommand($dossier->getId()));
    }

    private function apply(AbstractDossier&EntityWithMainDocument $dossier, AbstractMainDocument $mainDocument): void
    {
        $this->dossierMainDocumentValidator->validate($mainDocument);
        $dossier->setMainDocument($mainDocument);
    }
}
