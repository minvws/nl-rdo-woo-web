<?php

declare(strict_types=1);

namespace App\Api\Admin\AbstractMainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Symfony\Component\Uid\Uuid;

abstract readonly class AbstractMainDocumentProvider implements ProviderInterface
{
    abstract protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto;

    abstract protected function getAttachmentRepository(): MainDocumentRepositoryInterface;

    /**
     * @param array<array-key, string> $uriVariables
     * @param array<array-key, mixed>  $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?AbstractMainDocumentDto
    {
        unset($operation);
        unset($context);

        $document = $this->getAttachmentRepository()->findOneByDossierId(Uuid::fromString($uriVariables['dossierId']));
        if ($document === null) {
            return null;
        }

        return $this->fromEntityToDto($document);
    }
}