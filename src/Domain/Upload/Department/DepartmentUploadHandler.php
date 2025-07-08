<?php

declare(strict_types=1);

namespace App\Domain\Upload\Department;

use App\Domain\Upload\Event\UploadValidatedEvent;
use App\Domain\Upload\Process\EntityUploadStorer;
use App\Repository\DepartmentRepository;
use App\Service\Uploader\UploadGroupId;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

#[AsEventListener(event: UploadValidatedEvent::class, method: 'onUploadValidated')]
final readonly class DepartmentUploadHandler
{
    public function __construct(
        private DepartmentRepository $departmentRepository,
        private EntityUploadStorer $entityUploadStorer,
    ) {
    }

    public function onUploadValidated(UploadValidatedEvent $event): void
    {
        $uploadEntity = $event->uploadEntity;
        if ($uploadEntity->getUploadGroupId() !== UploadGroupId::DEPARTMENT) {
            return;
        }

        $departmentId = Uuid::fromString(
            $uploadEntity->getContext()->getString('departmentId'),
        );

        $department = $this->departmentRepository->findOne($departmentId);

        $this->entityUploadStorer->storeDepartmentAssetForEntity($uploadEntity, $department);

        $this->departmentRepository->save($department, true);
    }
}
