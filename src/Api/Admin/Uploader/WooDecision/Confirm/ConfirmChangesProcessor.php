<?php

declare(strict_types=1);

namespace App\Api\Admin\Uploader\WooDecision\Confirm;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use Webmozart\Assert\Assert;

final readonly class ConfirmChangesProcessor implements ProcessorInterface
{
    public function __construct(private DocumentFileService $documentFileService)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        unset($operation, $uriVariables, $context);

        $wooDecision = $data;
        Assert::isInstanceOf($wooDecision, WooDecision::class);

        $this->documentFileService->confirmUpdates($wooDecision);
    }
}
