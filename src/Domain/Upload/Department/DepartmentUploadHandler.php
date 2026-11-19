<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Department;

use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Upload\Event\UploadValidatedEvent;
use Shared\Domain\Upload\Process\EntityUploadStorer;
use Shared\Domain\Upload\UploadContextReader;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class DepartmentUploadHandler
{
    public function __construct(
        private DepartmentRepository $departmentRepository,
        private EntityUploadStorer $entityUploadStorer,
        private UploadContextReader $uploadContextReader,
    ) {
    }

    public function __invoke(UploadValidatedEvent $event): void
    {
        $uploadEntity = $event->uploadEntity;
        if ($uploadEntity->getUploadGroupId() !== UploadGroupId::DEPARTMENT) {
            return;
        }

        $departmentId = $this->uploadContextReader->getDepartmentId($uploadEntity);
        $department = $this->departmentRepository->findOne($departmentId);

        $this->entityUploadStorer->storeDepartmentAssetForEntity($uploadEntity, $department);

        $this->departmentRepository->save($department, true);
    }
}
