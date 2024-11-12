<?php

declare(strict_types=1);

namespace App\Api\Admin\Attachment;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\Command\CreateAttachmentCommand;
use App\Domain\Publication\Attachment\Command\DeleteAttachmentCommand;
use App\Domain\Publication\Attachment\Command\UpdateAttachmentCommand;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AttachmentProcessor implements ProcessorInterface
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * @return class-string<AttachmentCreateDto>
     */
    abstract protected function getCreateDtoClass(): string;

    /**
     * @return class-string<AttachmentUpdateDto>
     */
    abstract protected function getUpdateDtoClass(): string;

    abstract protected function fromEntityToDto(AbstractAttachment $entity): AttachmentDto;

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ?AttachmentDto {
        unset($context);

        Assert::object($data);
        Assert::allString($uriVariables);
        $dossierId = Uuid::fromString(strval($uriVariables['dossierId']));
        $attachmentId = isset($uriVariables['attachmentId']) ? Uuid::fromString($uriVariables['attachmentId']) : null;

        try {
            return match (true) {
                $operation instanceof Post && is_a($data, $this->getCreateDtoClass()) => $this->create($data, $dossierId),
                $operation instanceof Put && is_a($data, $this->getUpdateDtoClass()) => $this->update($data, $dossierId, $attachmentId),
                $operation instanceof Delete => $this->delete($dossierId, $attachmentId),
                default => null,
            };
        } catch (HandlerFailedException $exception) {
            $logicException = $exception->getPrevious();
            if ($logicException instanceof ValidationFailedException) {
                throw new ValidationException($logicException->getViolations());
            }

            throw $logicException ?? $exception;
        }
    }

    protected function create(AttachmentCreateDto $data, Uuid $dossierId): AttachmentDto
    {
        /** @var AbstractAttachment $attachment */
        $attachment = $this->handle(
            new CreateAttachmentCommand(
                dossierId: $dossierId,
                formalDate: $data->getFormalDateInstance(),
                internalReference: $data->internalReference,
                type: $data->type,
                language: $data->language,
                grounds: $data->grounds,
                uploadFileReference: $data->uploadUuid,
            )
        );

        return $this->fromEntityToDto($attachment);
    }

    protected function update(AttachmentUpdateDto $data, Uuid $dossierId, ?Uuid $attachmentId): AttachmentDto
    {
        Assert::notNull($attachmentId);

        /** @var AbstractAttachment $attachment */
        $attachment = $this->handle(
            new UpdateAttachmentCommand(
                dossierId: $dossierId,
                attachmentId: $attachmentId,
                formalDate: $data->getFormalDateInstance(),
                internalReference: $data->internalReference,
                type: $data->type,
                language: $data->language,
                grounds: $data->grounds,
                uploadFileReference: $data->uploadUuid,
            )
        );

        return $this->fromEntityToDto($attachment);
    }

    private function delete(Uuid $dossierId, ?Uuid $attachmentId): null
    {
        Assert::notNull($attachmentId);

        $this->handle(
            new DeleteAttachmentCommand(
                dossierId: $dossierId,
                attachmentId: $attachmentId,
            )
        );

        return null;
    }
}
