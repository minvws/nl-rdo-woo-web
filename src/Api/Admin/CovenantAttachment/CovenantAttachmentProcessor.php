<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantAttachment;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use App\Domain\Publication\Dossier\Type\Covenant\Command\CreateCovenantAttachmentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\Command\DeleteCovenantAttachmentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\Command\UpdateCovenantAttachmentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class CovenantAttachmentProcessor implements ProcessorInterface
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): ?CovenantAttachmentDto {
        unset($context);

        Assert::allString($uriVariables);
        $dossierId = Uuid::fromString(strval($uriVariables['dossierId']));
        $attachmentId = isset($uriVariables['attachmentId']) ? Uuid::fromString($uriVariables['attachmentId']) : null;

        try {
            return match (true) {
                $operation instanceof Post && $data instanceof CovenantAttachmentCreateDto => $this->create($data, $dossierId),
                $operation instanceof Put && $data instanceof CovenantAttachmentUpdateDto => $this->update($data, $dossierId, $attachmentId),
                $operation instanceof Delete => $this->delete($dossierId, $attachmentId),
                default => null,
            };
        } catch (HandlerFailedException $exception) {
            $logicException = $exception->getPrevious();
            if ($logicException instanceof ValidationFailedException) {
                throw new ValidationException($logicException->getViolations(), previous: $logicException);
            }

            throw $logicException ?? $exception;
        }
    }

    private function create(CovenantAttachmentCreateDto $data, Uuid $dossierId): CovenantAttachmentDto
    {
        /** @var CovenantAttachment $attachment */
        $attachment = $this->handle(
            new CreateCovenantAttachmentCommand(
                dossierId: $dossierId,
                formalDate: $data->getFormalDateInstance(),
                internalReference: $data->internalReference ?? '',
                type: $data->type,
                language: $data->language,
                grounds: $data->grounds,
                uploadFileReference: $data->uploadUuid,
                name: $data->name,
            )
        );

        return CovenantAttachmentDto::fromEntity($attachment);
    }

    private function update(CovenantAttachmentUpdateDto $data, Uuid $dossierId, ?Uuid $attachmentId): CovenantAttachmentDto
    {
        Assert::notNull($attachmentId);

        /** @var CovenantAttachment $attachment */
        $attachment = $this->handle(
            new UpdateCovenantAttachmentCommand(
                dossierId: $dossierId,
                attachmentId: $attachmentId,
                formalDate: $data->getFormalDateInstance(),
                internalReference: $data->internalReference,
                type: $data->type,
                language: $data->language,
                grounds: $data->grounds,
                name: $data->name,
                uploadFileReference: $data->uploadUuid,
            )
        );

        return CovenantAttachmentDto::fromEntity($attachment);
    }

    private function delete(Uuid $dossierId, ?Uuid $attachmentId): null
    {
        Assert::notNull($attachmentId);

        $this->handle(
            new DeleteCovenantAttachmentCommand(
                dossierId: $dossierId,
                attachmentId: $attachmentId,
            )
        );

        return null;
    }
}
