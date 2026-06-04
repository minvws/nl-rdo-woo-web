<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Upload\Process\EntityUploadStorer;
use Shared\Domain\Upload\UploadEntity;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

readonly class ProcessUploadedDocumentAction
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private DocumentFileService $documentFileService,
        private EntityUploadStorer $entityUploadStorer,
    ) {
    }

    public function execute(UploadEntity $uploadEntity): void
    {
        $wooDecisionId = Uuid::fromString($uploadEntity->getContext()->getString('dossierId'));

        $wooDecision = $this->wooDecisionRepository->findOneByDossierId($wooDecisionId);

        $fileName = $uploadEntity->getFilename();
        Assert::notNull($fileName);

        $documentFileSet = $this->documentFileService->getDocumentFileSet($wooDecision);
        $documentFileUpload = $this->documentFileService->createNewUpload($documentFileSet, $fileName);

        $this->entityUploadStorer->storeUploadForEntity($uploadEntity, $documentFileUpload);

        $this->documentFileService->finishUpload($documentFileSet, $documentFileUpload);
    }
}
