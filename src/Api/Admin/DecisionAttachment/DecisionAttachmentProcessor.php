<?php

declare(strict_types=1);

namespace App\Api\Admin\DecisionAttachment;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\CreateDecisionAttachmentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\DeleteDecisionAttachmentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\UpdateDecisionAttachmentCommand;
use App\Entity\DecisionAttachment;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class DecisionAttachmentProcessor implements ProcessorInterface
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
    ): ?DecisionAttachmentDto {
        unset($context);

        Assert::allString($uriVariables);
        $dossierId = Uuid::fromString(strval($uriVariables['dossierId']));
        $decisionAttachmentId = isset($uriVariables['decisionAttachmentId']) ? Uuid::fromString($uriVariables['decisionAttachmentId']) : null;

        try {
            return match (true) {
                $operation instanceof Post && $data instanceof DecisionAttachmentCreateDto => $this->create($data, $dossierId),
                $operation instanceof Put && $data instanceof DecisionAttachmentUpdateDto => $this->update($data, $dossierId, $decisionAttachmentId),
                $operation instanceof Delete => $this->delete($dossierId, $decisionAttachmentId),
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

    private function create(DecisionAttachmentCreateDto $data, Uuid $dossierId): DecisionAttachmentDto
    {
        /** @var DecisionAttachment $decisionAttachment */
        $decisionAttachment = $this->handle(
            new CreateDecisionAttachmentCommand(
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

        return DecisionAttachmentDto::fromEntity($decisionAttachment);
    }

    private function update(DecisionAttachmentUpdateDto $data, Uuid $dossierId, ?Uuid $decisionAttachmentId): DecisionAttachmentDto
    {
        Assert::notNull($decisionAttachmentId);

        /** @var DecisionAttachment $decisionAttachment */
        $decisionAttachment = $this->handle(
            new UpdateDecisionAttachmentCommand(
                dossierId: $dossierId,
                decisionAttachmentId: $decisionAttachmentId,
                formalDate: $data->getFormalDateInstance(),
                internalReference: $data->internalReference,
                type: $data->type,
                language: $data->language,
                grounds: $data->grounds,
                name: $data->name,
                uploadFileReference: $data->uploadUuid,
            )
        );

        return DecisionAttachmentDto::fromEntity($decisionAttachment);
    }

    private function delete(Uuid $dossierId, ?Uuid $decisionAttachmentId): null
    {
        Assert::notNull($decisionAttachmentId);

        $this->handle(
            new DeleteDecisionAttachmentCommand(
                dossierId: $dossierId,
                decisionAttachmentId: $decisionAttachmentId,
            )
        );

        return null;
    }
}
