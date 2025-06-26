<?php

declare(strict_types=1);

namespace App\Domain\Upload\Department;

use App\Domain\Department\DepartmentFileService;
use App\Domain\Upload\UploadedFile;
use App\Repository\DepartmentRepository;
use App\Service\Uploader\UploadGroupId;
use Oneup\UploaderBundle\Event\PostUploadEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Oneup\UploaderBundle\UploadEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AsEventListener(event: UploadEvents::POST_UPLOAD . '.department', method: 'onPostUpload')]
final readonly class DepartmentPostUpload
{
    public function __construct(
        private DepartmentRepository $departmentRepository,
        private DepartmentFileService $departmentFileService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function onPostUpload(PostUploadEvent $event): void
    {
        $uploaderGroupId = UploadGroupId::from($event->getRequest()->getPayload()->getString('groupId'));
        if ($uploaderGroupId !== UploadGroupId::DEPARTMENT) {
            throw new ValidationException('error.invalid_group');
        }

        $departmentId = Uuid::fromString($event->getRequest()->attributes->getString('departmentId'));

        $department = $this->departmentRepository->findOne($departmentId);

        $file = $event->getRequest()->files->get('file');
        Assert::isInstanceOf($file, SymfonyUploadedFile::class);

        $uploadedFile = new UploadedFile($event->getFile()->getPathname(), $file->getClientOriginalName());

        $department = $this->departmentFileService->addDepartmentLogo($department, $uploadedFile);

        /** @var array<array-key,scalar> $data */
        $data = $event->getResponse()['data'] ?? [];

        $data['department'] = [
            'asset_endpoint' => $this->urlGenerator->generate(
                'app_admin_department_assets_download',
                ['id' => $departmentId, 'file' => $department->getFileInfo()->getName()],
            ),
        ];

        $event->getResponse()['data'] = $data;
    }
}
