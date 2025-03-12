<?php

declare(strict_types=1);

namespace App\Api\Admin\Uploader\WooDecision\Process;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class ProcessUploadsProvider implements ProviderInterface
{
    public function __construct(private WooDecisionRepository $wooDecisionRepository)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WooDecision
    {
        unset($operation, $context);

        $dossierId = $uriVariables['dossierId'];
        Assert::isInstanceOf($dossierId, Uuid::class);

        return $this->wooDecisionRepository->findOne($dossierId);
    }
}
