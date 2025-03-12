<?php

declare(strict_types=1);

namespace App\Api\Admin\Uploader\WooDecision\Status;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class UploadStatusProvider implements ProviderInterface
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private DocumentFileService $documentFileService,
        private UploadStatusDtoFactory $uploadStatusDtoFactory,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UploadStatusDto
    {
        unset($operation, $context);

        $dossierId = $uriVariables['dossierId'];
        Assert::isInstanceOf($dossierId, Uuid::class);

        $wooDecision = $this->wooDecisionRepository->findOne($dossierId);
        $documentFileSet = $this->documentFileService->getDocumentFileSet($wooDecision);

        return $this->uploadStatusDtoFactory->make($wooDecision, $documentFileSet);
    }
}
