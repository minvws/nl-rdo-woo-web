<?php

declare(strict_types=1);

namespace Admin\Api\Admin\NoticeNotPublic;

use Admin\Api\Admin\ApiDossierAccessChecker;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Command\CreateNoticeNotPublicCommand;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Command\DeleteNoticeNotPublicCommand;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Command\UpdateNoticeNotPublicCommand;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Webmozart\Assert\Assert;

final class NoticeNotPublicProcessor implements ProcessorInterface
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly ApiDossierAccessChecker $dossierAccessChecker,
    ) {
        $this->messageBus = $messageBus;
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ?NoticeNotPublicDto {
        unset($context);

        Assert::keyExists($uriVariables, 'dossierId');
        $dossierId = $uriVariables['dossierId'];
        Assert::string($dossierId);

        $dossierId = Uuid::fromString($dossierId);
        $this->dossierAccessChecker->ensureUserIsAllowedToUpdateDossier($dossierId);

        Assert::object($data);
        try {
            return match (true) {
                $operation instanceof Post && $data instanceof NoticeNotPublicInput => $this->create($data, $dossierId),
                $operation instanceof Put && $data instanceof NoticeNotPublicInput => $this->update($data, $dossierId),
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

    private function create(NoticeNotPublicInput $data, Uuid $dossierId): NoticeNotPublicDto
    {
        $noticeNotPublic = $this->handle(
            new CreateNoticeNotPublicCommand(
                dossierId: $dossierId,
                documentName: $data->documentName,
                formalDate: $data->formalDate,
                grounds: $data->grounds,
                explanation: $data->explanation,
            ),
        );
        Assert::isInstanceOf($noticeNotPublic, NoticeNotPublic::class);

        return NoticeNotPublicDto::fromEntity($noticeNotPublic);
    }

    private function update(NoticeNotPublicInput $data, Uuid $dossierId): NoticeNotPublicDto
    {
        $noticeNotPublic = $this->handle(
            new UpdateNoticeNotPublicCommand(
                dossierId: $dossierId,
                documentName: $data->documentName,
                formalDate: $data->formalDate,
                grounds: $data->grounds,
                explanation: $data->explanation,
            ),
        );
        Assert::isInstanceOf($noticeNotPublic, NoticeNotPublic::class);

        return NoticeNotPublicDto::fromEntity($noticeNotPublic);
    }

    private function delete(Uuid $dossierId): null
    {
        $this->handle(new DeleteNoticeNotPublicCommand($dossierId));

        return null;
    }
}
