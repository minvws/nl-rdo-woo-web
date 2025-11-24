<?php

declare(strict_types=1);

namespace Shared\Api\Admin\AbstractMainDocument;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Shared\Api\Admin\ApiDossierAccessChecker;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Symfony\Component\Uid\Uuid;

abstract readonly class AbstractMainDocumentProvider implements ProviderInterface
{
    public function __construct(
        private ApiDossierAccessChecker $dossierAccessChecker,
        private EntityManagerInterface $entityManager,
    ) {
    }

    abstract protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto;

    /**
     * @return class-string<AbstractMainDocument>
     */
    abstract protected function getEntityClass(): string;

    /**
     * @param array<array-key, string> $uriVariables
     * @param array<array-key, mixed>  $context
     *
     * @return array<array-key,AbstractMainDocumentDto>|AbstractMainDocumentDto|object[]|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|AbstractMainDocumentDto|null
    {
        unset($context);

        $dossierId = Uuid::fromString($uriVariables['dossierId']);

        $this->dossierAccessChecker->ensureUserIsAllowedToUpdateDossier($dossierId);

        $document = $this->getRepository()->findOneByDossierId($dossierId);

        if ($operation instanceof CollectionOperationInterface) {
            return $document ? [$this->fromEntityToDto($document)] : [];
        }

        return $document ? $this->fromEntityToDto($document) : null;
    }

    private function getRepository(): MainDocumentRepositoryInterface
    {
        /** @var EntityRepository&MainDocumentRepositoryInterface */
        return $this->entityManager->getRepository(
            $this->getEntityClass(),
        );
    }
}
