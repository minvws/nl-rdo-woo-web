<?php

declare(strict_types=1);

namespace Shared\Api\Admin\Uploader\WooDecision\Reject;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Webmozart\Assert\Assert;

final readonly class RejectChangesProcessor implements ProcessorInterface
{
    public function __construct(private DocumentFileService $documentFileService)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        unset($operation, $uriVariables, $context);

        $wooDecision = $data;
        Assert::isInstanceOf($wooDecision, WooDecision::class);

        $this->documentFileService->rejectUpdates($wooDecision);
    }
}
