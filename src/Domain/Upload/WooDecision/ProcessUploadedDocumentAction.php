<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Upload\Process\EntityUploadStorer;
use Shared\Domain\Upload\UploadContextReader;
use Shared\Domain\Upload\UploadEntity;
use Webmozart\Assert\Assert;

readonly class ProcessUploadedDocumentAction
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private DocumentFileService $documentFileService,
        private EntityUploadStorer $entityUploadStorer,
        private UploadContextReader $uploadContextReader,
    ) {
    }

    public function execute(UploadEntity $uploadEntity): void
    {
        $dossierId = $this->uploadContextReader->getDossierId($uploadEntity);
        $wooDecision = $this->wooDecisionRepository->findOneByDossierId($dossierId);

        $fileName = $uploadEntity->getFilename();
        Assert::notNull($fileName);

        $documentFileSet = $this->documentFileService->getDocumentFileSet($wooDecision);
        $documentFileUpload = $this->documentFileService->createNewUpload($documentFileSet, $fileName);

        $this->entityUploadStorer->storeUploadForEntity($uploadEntity, $documentFileUpload);

        $this->documentFileService->finishUpload($documentFileSet, $documentFileUpload);
    }
}
