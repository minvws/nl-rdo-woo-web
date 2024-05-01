<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantDocument;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use App\Domain\Publication\Dossier\Type\Covenant\Command\CreateCovenantDocumentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\Command\DeleteCovenantDocumentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\Command\UpdateCovenantDocumentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class CovenantDocumentProcessor implements ProcessorInterface
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
    ): ?CovenantDocumentDto {
        unset($context);

        $dossierId = Uuid::fromString(strval($uriVariables['dossierId']));

        try {
            return match (true) {
                $operation instanceof Post && $data instanceof CovenantDocumentCreateDto => $this->create($data, $dossierId),
                $operation instanceof Put && $data instanceof CovenantDocumentUpdateDto => $this->update($data, $dossierId),
                $operation instanceof Delete => $this->delete($dossierId),
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

    private function create(CovenantDocumentCreateDto $data, Uuid $dossierId): CovenantDocumentDto
    {
        /** @var CovenantDocument $covenantDocument */
        $covenantDocument = $this->handle(
            new CreateCovenantDocumentCommand(
                dossierId: $dossierId,
                formalDate: $data->getFormalDateInstance(),
                internalReference: $data->internalReference,
                language: $data->language,
                grounds: $data->grounds,
                uploadFileReference: $data->uploadUuid,
                name: $data->name,
            )
        );

        return CovenantDocumentDto::fromEntity($covenantDocument);
    }

    private function update(CovenantDocumentUpdateDto $data, Uuid $dossierId): CovenantDocumentDto
    {
        /** @var CovenantDocument $covenantDocument */
        $covenantDocument = $this->handle(
            new UpdateCovenantDocumentCommand(
                dossierId: $dossierId,
                formalDate: $data->getFormalDateInstance(),
                internalReference: $data->internalReference,
                language: $data->language,
                grounds: $data->grounds,
                uploadFileReference: $data->uploadUuid,
                name: $data->name,
            )
        );

        return CovenantDocumentDto::fromEntity($covenantDocument);
    }

    private function delete(Uuid $dossierId): null
    {
        $this->handle(
            new DeleteCovenantDocumentCommand(
                dossierId: $dossierId,
            )
        );

        return null;
    }
}
