<?php

declare(strict_types=1);

namespace App\Api\Admin\DecisionAttachment;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProviderInterface;
use App\Entity\DecisionAttachment;
use App\Repository\DecisionAttachmentRepository;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class DecisionAttachmentProvider implements ProviderInterface
{
    public function __construct(
        private DecisionAttachmentRepository $repository,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     * @param array<array-key, mixed>  $context
     *
     * @return array<array-key,DecisionAttachmentDto>|DecisionAttachmentDto|object[]|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|DecisionAttachmentDto|null
    {
        unset($context);

        Assert::allString($uriVariables);
        $dossierId = Uuid::fromString(strval($uriVariables['dossierId']));
        $decisionAttachmentId = isset($uriVariables['decisionAttachmentId']) ? Uuid::fromString($uriVariables['decisionAttachmentId']) : null;

        if ($operation instanceof CollectionOperationInterface) {
            return $this->repository->findAllForDossier($dossierId)->map(
                fn (DecisionAttachment $entity): DecisionAttachmentDto => DecisionAttachmentDto::fromEntity($entity)
            )->toArray();
        }

        if ($operation instanceof Post || $decisionAttachmentId === null) {
            return null;
        }

        return DecisionAttachmentDto::fromEntity(
            $this->repository->findOneForDossier(
                $dossierId,
                $decisionAttachmentId,
            )
        );
    }
}
