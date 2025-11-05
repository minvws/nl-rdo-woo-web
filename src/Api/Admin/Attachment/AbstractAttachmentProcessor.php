<?php

declare(strict_types=1);

namespace App\Api\Admin\Attachment;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Api\Admin\ApiDossierAccessChecker;
use App\Domain\Publication\Attachment\Command\CreateAttachmentCommand;
use App\Domain\Publication\Attachment\Command\DeleteAttachmentCommand;
use App\Domain\Publication\Attachment\Command\UpdateAttachmentCommand;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
abstract class AbstractAttachmentProcessor implements ProcessorInterface
{
    use HandleTrait;

    public function __construct(
        protected readonly ApiDossierAccessChecker $dossierAccessChecker,
        protected readonly AttachmentDtoFactory $dtoFactory,
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    abstract protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto;

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ?AbstractAttachmentDto {
        unset($context);

        Assert::object($data);
        Assert::allString($uriVariables);

        $dossierId = Uuid::fromString(strval($uriVariables['dossierId']));
        $this->dossierAccessChecker->ensureUserIsAllowedToUpdateDossier($dossierId);

        $attachmentId = isset($uriVariables['attachmentId']) ? Uuid::fromString($uriVariables['attachmentId']) : null;

        try {
            return match (true) {
                $operation instanceof Post && $data instanceof AttachmentCreateDto => $this->create($data, $dossierId),
                $operation instanceof Put && $data instanceof AttachmentUpdateDto => $this->update($data, $dossierId, $attachmentId),
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

    protected function create(AttachmentCreateDto $data, Uuid $dossierId): AbstractAttachmentDto
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

    protected function update(AttachmentUpdateDto $data, Uuid $dossierId, ?Uuid $attachmentId): AbstractAttachmentDto
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
