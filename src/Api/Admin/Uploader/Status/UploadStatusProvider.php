<?php

declare(strict_types=1);

namespace Shared\Api\Admin\Uploader\Status;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Upload\Exception\UploadNotFoundException;
use Shared\Domain\Upload\UploadEntityRepository;

final readonly class UploadStatusProvider implements ProviderInterface
{
    public function __construct(
        private UploadEntityRepository $repository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UploadStatusDto
    {
        unset($operation, $context);

        $entity = $this->repository->findOneBy(['uploadId' => $uriVariables['uploadId']]);

        if ($entity === null) {
            throw new UploadNotFoundException();
        }

        return UploadStatusDto::fromEntity($entity);
    }
}
