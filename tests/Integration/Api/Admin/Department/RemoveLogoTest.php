<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Api\Admin\Department;

use Doctrine\ORM\EntityManagerInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\SourceType;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\UserFactory;
use Shared\Tests\Integration\Api\Admin\AdminApiTestCase;
use Shared\Tests\Integration\VfsStreamHelpers;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class RemoveLogoTest extends AdminApiTestCase
{
    use VfsStreamHelpers;

    private vfsStreamDirectory $root;
    private DepartmentRepository $departmentRepository;
    private EntityManagerInterface $doctrine;

    private string $assetsPath;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->root = vfsStream::setup('root');

        $this->departmentRepository = self::getContainer()->get(DepartmentRepository::class);
        $this->doctrine = self::getContainer()->get(EntityManagerInterface::class);

        $this->assetsPath = self::getContainer()->getParameter('assets_path');
    }

    public function testRemoveLogo(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $department = DepartmentFactory::createOne([
            'name' => 'Department of Magic',
        ])->_real();

        $fileInfo = FileInfoFactory::createOne([
            'name' => $name = 'logo.svg',
            'mimetype' => 'image/svg+xml',
            'type' => 'vector-image',
            'sourceType' => SourceType::UNKNOWN,
            'path' => sprintf('department/%s/%s', $department->getId(), $name),
        ]);

        $fullPath = sprintf('%s%s/department/%s/%s', $this->root->url(), $this->assetsPath, $department->getId(), $name);

        $department->setFileInfo($fileInfo);
        $this->doctrine->persist($department);
        $this->doctrine->flush();

        $this->createFileForEntityOnVfs($department, $this->assetsPath);

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/department/%s/logo', $department->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $department = $this->departmentRepository->find($department->getId());

        self::assertFalse($department?->getFileInfo()->isUploaded(), 'Logo is not uploaded');
        self::assertFalse(file_exists($fullPath), 'Logo file does not exist');
    }

    public function testRemoveLogoOnDepartmentWithoutCurrentlyHavingALogo(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $department = DepartmentFactory::createOne([
            'name' => 'Department of Magic',
            'fileInfo' => new FileInfo(),
        ])->_real();

        self::assertFalse($department->getFileInfo()->isUploaded());

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/department/%s/logo', $department->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $department = $this->departmentRepository->find($department->getId());

        self::assertFalse($department?->getFileInfo()->isUploaded());
    }

    public function testRemoveLogoOnNonExistingDepartment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $nonExistingDepartmentId = Uuid::v6();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/department/%s/logo', $nonExistingDepartmentId),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testRemoveLogoWithoutHavingAccess(): void
    {
        $user = UserFactory::new()
            ->asViewAccess()
            ->isEnabled()
            ->create()
            ->_real();

        $department = DepartmentFactory::createOne([
            'name' => 'Department of Magic',
        ])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/department/%s/logo', $department->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
