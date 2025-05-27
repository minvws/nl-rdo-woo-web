<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin\Department;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Publication\FileInfo;
use App\Repository\DepartmentRepository;
use App\SourceType;
use App\Tests\Factory\DepartmentFactory;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use App\Tests\Integration\VfsStreamHelpers;
use Doctrine\ORM\EntityManagerInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class RemoveLogoTest extends ApiTestCase
{
    use IntegrationTestTrait;
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

        $client = static::createClient()->loginUser($user, 'balie');
        $client->request(
            Request::METHOD_DELETE,
            sprintf('/balie/api/department/%s/logo', $department->getId()),
            [
                'headers' => ['Accept' => 'application/json'],
            ],
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

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_DELETE,
            sprintf('/balie/api/department/%s/logo', $department->getId()),
            [
                'headers' => ['Accept' => 'application/json'],
            ],
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

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_DELETE,
            sprintf('/balie/api/department/%s/logo', $nonExistingDepartmentId),
            [
                'headers' => ['Accept' => 'application/json'],
            ],
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

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_DELETE,
            sprintf('/balie/api/department/%s/logo', $department->getId()),
            [
                'headers' => ['Accept' => 'application/json'],
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
