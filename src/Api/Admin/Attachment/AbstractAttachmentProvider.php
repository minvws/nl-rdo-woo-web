<?php

declare(strict_types=1);

namespace App\Api\Admin\Attachment;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

abstract readonly class AbstractAttachmentProvider implements ProviderInterface
{
    abstract protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto;

    abstract protected function getAttachmentRepository(): AttachmentRepositoryInterface;

    /**
     * @param array<array-key,string> $uriVariables
     * @param array<array-key,mixed>  $context
     *
     * @return array<array-key,AbstractAttachmentDto>|AbstractAttachmentDto|object[]|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|AbstractAttachmentDto|null
    {
        unset($context);

        Assert::allString($uriVariables);
        $dossierId = Uuid::fromString(strval($uriVariables['dossierId']));
        $attachmentId = isset($uriVariables['attachmentId']) ? Uuid::fromString($uriVariables['attachmentId']) : null;

        if ($operation instanceof CollectionOperationInterface) {
            return $this->getAttachmentRepository()
                ->findAllForDossier($dossierId)
                ->map(fn (AbstractAttachment $entity): AbstractAttachmentDto => $this->fromEntityToDto($entity))
                ->toArray();
        }

        if ($operation instanceof Post || $attachmentId === null) {
            return null;
        }

        return $this->fromEntityToDto(
            $this->getAttachmentRepository()->findOneForDossier($dossierId, $attachmentId),
        );
    }
}
