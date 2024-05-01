<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocumentRepository;
use Symfony\Component\Uid\Uuid;

final readonly class CovenantDocumentProvider implements ProviderInterface
{
    public function __construct(
        private CovenantDocumentRepository $repository,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     * @param array<array-key, mixed>  $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?CovenantDocumentDto
    {
        unset($operation);
        unset($context);

        $document = $this->repository->findOneByDossierId(Uuid::fromString($uriVariables['dossierId']));
        if ($document === null) {
            return null;
        }

        return CovenantDocumentDto::fromEntity($document);
    }
}
