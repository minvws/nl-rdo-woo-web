<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantAttachment;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachmentRepository;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class CovenantAttachmentProvider implements ProviderInterface
{
    public function __construct(
        private CovenantAttachmentRepository $repository,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     * @param array<array-key, mixed>  $context
     *
     * @return array<array-key,CovenantAttachmentDto>|CovenantAttachmentDto|object[]|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|CovenantAttachmentDto|null
    {
        unset($context);

        Assert::allString($uriVariables);
        $dossierId = Uuid::fromString(strval($uriVariables['dossierId']));
        $attachmentId = isset($uriVariables['attachmentId']) ? Uuid::fromString($uriVariables['attachmentId']) : null;

        if ($operation instanceof CollectionOperationInterface) {
            return $this->repository->findAllForDossier($dossierId)->map(
                fn (CovenantAttachment $entity): CovenantAttachmentDto => CovenantAttachmentDto::fromEntity($entity)
            )->toArray();
        }

        if ($operation instanceof Post || $attachmentId === null) {
            return null;
        }

        return CovenantAttachmentDto::fromEntity(
            $this->repository->findOneForDossier(
                $dossierId,
                $attachmentId,
            )
        );
    }
}
