<?php

declare(strict_types=1);

namespace App\Api\Admin\Document;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Api\Admin\Attachment\AttachmentCreateDto;
use App\Api\Admin\Attachment\AttachmentUpdateDto;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use App\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use App\Domain\Publication\MainDocument\Command\UpdateMainDocumentCommand;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class DocumentProcessor implements ProcessorInterface
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

    abstract protected function fromEntityToDto(AbstractMainDocument $entity): DocumentDto;

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ?DocumentDto {
        unset($context);

        $dossierId = $uriVariables['dossierId'] ?? '';

        Assert::validArrayKey($dossierId);
        Assert::object($data);

        $dossierId = Uuid::fromString((string) $dossierId);

        try {
            return match (true) {
                $operation instanceof Post && is_a($data, $this->getCreateDtoClass()) => $this->create($data, $dossierId),
                $operation instanceof Put && is_a($data, $this->getUpdateDtoClass()) => $this->update($data, $dossierId),
                $operation instanceof Delete => $this->delete($dossierId),
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

    private function create(AttachmentCreateDto $data, Uuid $dossierId): DocumentDto
    {
        /** @var AbstractMainDocument $mainDocument */
        $mainDocument = $this->handle(
            new CreateMainDocumentCommand(
                dossierId: $dossierId,
                formalDate: $data->getFormalDateInstance(),
                internalReference: $data->internalReference,
                type: $data->type,
                language: $data->language,
                grounds: $data->grounds,
                uploadFileReference: $data->uploadUuid,
            )
        );

        return $this->fromEntityToDto($mainDocument);
    }

    private function update(AttachmentUpdateDto $data, Uuid $dossierId): DocumentDto
    {
        /** @var AbstractMainDocument $mainDocument */
        $mainDocument = $this->handle(
            new UpdateMainDocumentCommand(
                dossierId: $dossierId,
                formalDate: $data->getFormalDateInstance(),
                internalReference: $data->internalReference,
                type: $data->type,
                language: $data->language,
                grounds: $data->grounds,
                uploadFileReference: $data->uploadUuid,
            )
        );

        return $this->fromEntityToDto($mainDocument);
    }

    private function delete(Uuid $dossierId): null
    {
        $this->handle(
            new DeleteMainDocumentCommand(
                dossierId: $dossierId,
            )
        );

        return null;
    }
}
