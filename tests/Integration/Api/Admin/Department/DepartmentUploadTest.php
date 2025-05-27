<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin\Department;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Domain\Publication\FileInfo;
use App\Repository\DepartmentRepository;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Factory\DepartmentFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use org\bovigo\vfs\content\LargeFileContent;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final class DepartmentUploadTest extends ApiTestCase
{
    use IntegrationTestTrait;

    private vfsStreamDirectory $root;
    private DepartmentRepository $departmentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();

        self::bootKernel();

        $this->departmentRepository = self::getContainer()->get(DepartmentRepository::class);
    }

    public function testUploadingFileAsSuperAdmin(): void
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

        $client = static::createClient()->loginUser($user, 'balie');

        vfsStream::newFile($fileName = 'my_logo.svg')
            ->withContent($this->getSVGContent())
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: sprintf('%s/%s', $this->root->url(), $fileName),
            originalName: $fileName,
        );

        $this->uploadFile($client, $department->getId(), $uploadFile);
        self::assertResponseIsSuccessful();

        /** @var array<array-key,scalar|array<array-key,scalar>> $data */
        $data = $client->getResponse()?->toArray(false);

        $endpoint = $data['data']['department']['asset_endpoint'] ?? '';
        Assert::string($endpoint);
        unset($data['data']['department']);

        self::assertMatchesJsonSnapshot($data);
        self::assertMatchesRegularExpression('#^/assets/department/[a-z-0-9-]{36}/[a-z-0-9-]{36}.svg$#', $endpoint);

        $department = $this->departmentRepository->findOne($department->getId());

        self::assertTrue($department->getFileInfo()->isUploaded(), 'Logo is uploaded');
        self::assertMatchesRegularExpression('#^[a-z-0-9-]{36}.svg$#', $department->getFileInfo()->getName() ?? '');
    }

    public function testUploadingFileAsOrganisationAdmin(): void
    {
        $department = DepartmentFactory::createOne([
            'name' => 'Department of Magic',
            'fileInfo' => new FileInfo(),
        ])->_real();

        $organisation = OrganisationFactory::createOne([
            'departments' => [$department],
        ])->_real();

        $user = UserFactory::new()
            ->asOrganisationAdmin()
            ->isEnabled()
            ->create([
                'organisation' => $organisation,
            ])
            ->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        vfsStream::newFile($fileName = 'my_logo.svg')
            ->withContent($this->getSVGContent())
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: sprintf('%s/%s', $this->root->url(), $fileName),
            originalName: $fileName,
        );

        $this->uploadFile($client, $department->getId(), $uploadFile);
        self::assertResponseIsSuccessful();

        /** @var array<array-key,scalar|array<array-key,scalar>> $data */
        $data = $client->getResponse()?->toArray(false);

        $endpoint = $data['data']['department']['asset_endpoint'] ?? '';
        Assert::string($endpoint);
        unset($data['data']['department']);

        self::assertMatchesJsonSnapshot($data);
        self::assertMatchesRegularExpression('#^/assets/department/[a-z-0-9-]{36}/[a-z-0-9-]{36}.svg$#', $endpoint);

        $department = $this->departmentRepository->findOne($department->getId());

        self::assertTrue($department->getFileInfo()->isUploaded(), 'Logo is uploaded');
        self::assertMatchesRegularExpression('#^[a-z-0-9-]{36}.svg$#', $department->getFileInfo()->getName() ?? '');
    }

    public function testUploadingFileAsOrganisationAdminFromDifferentOrganisation(): void
    {
        $department = DepartmentFactory::createOne([
            'name' => 'Department of Magic',
            'fileInfo' => new FileInfo(),
        ])->_real();
        OrganisationFactory::createOne(['departments' => [$department]]);

        $usersOrganisation = OrganisationFactory::createOne([
            'departments' => [],
        ])->_real();

        $user = UserFactory::new()
            ->asOrganisationAdmin()
            ->isEnabled()
            ->create([
                'organisation' => $usersOrganisation,
            ])
            ->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        vfsStream::newFile($fileName = 'my_logo.svg')
            ->withContent($this->getSVGContent())
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: sprintf('%s/%s', $this->root->url(), $fileName),
            originalName: $fileName,
        );

        $this->uploadFile($client, $department->getId(), $uploadFile);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUploadingFileWhileCustomTemplateExists(): void
    {
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        $customemplate = sprintf('%s/templates/public/department/custom/ccmo.html.twig', $projectDir);

        $this->assertFileExists($customemplate);

        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $department = DepartmentFactory::createOne([
            'name' => 'CCMO',
            'shortTag' => 'CCMO',
            'slug' => 'ccmo',
            'fileInfo' => new FileInfo(),
        ])->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        vfsStream::newFile($fileName = 'my_logo.svg')
            ->withContent($this->getSVGContent())
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: sprintf('%s/%s', $this->root->url(), $fileName),
            originalName: $fileName,
        );

        $this->uploadFile($client, $department->getId(), $uploadFile);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUploadingToNonExistingDepartment(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        vfsStream::newFile($fileName = 'my_logo.svg')
            ->withContent('This is a test file.')
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: sprintf('%s/%s', $this->root->url(), $fileName),
            originalName: $fileName,
        );

        $this->uploadFile($client, Uuid::v6(), $uploadFile);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUploadingToDepartmentWithoutAccess(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $department = DepartmentFactory::createOne(['name' => 'Department of Magic']);

        $client = static::createClient()->loginUser($user, 'balie');

        vfsStream::newFile($fileName = 'my_logo.svg')
            ->withContent('This is a test file.')
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: sprintf('%s/%s', $this->root->url(), $fileName),
            originalName: $fileName,
        );

        $this->uploadFile($client, $department->getId(), $uploadFile);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUploadingToDepartmentWithWrongGroupId(): void
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

        $client = static::createClient()->loginUser($user, 'balie');

        vfsStream::newFile($fileName = 'my_textfile.txt')
            ->withContent('Hello, World')
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: sprintf('%s/%s', $this->root->url(), $fileName),
            originalName: $fileName,
        );

        $this->uploadFile($client, $department->getId(), $uploadFile, UploadGroupId::MAIN_DOCUMENTS);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        self::assertMatchesJsonSnapshot($client->getResponse()?->toArray(false));

        $department = $this->departmentRepository->findOne($department->getId());

        self::assertFalse($department->getFileInfo()->isUploaded(), 'Logo is not uploaded');
    }

    public function testUploadingToDepartmentWithToLargeFile(): void
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

        $client = static::createClient()->loginUser($user, 'balie');

        vfsStream::newFile($fileName = 'my_logo.svg')
            ->withContent(LargeFileContent::withMegabytes(50))
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: sprintf('%s/%s', $this->root->url(), $fileName),
            originalName: $fileName,
        );

        $this->uploadFile($client, $department->getId(), $uploadFile);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        /** @var array<array-key,scalar|array<array-key,scalar>> $data */
        $data = $client->getResponse()?->toArray(false);

        self::assertMatchesJsonSnapshot($data);
    }

    private function uploadFile(
        Client $client,
        Uuid $departmentId,
        UploadedFile $file,
        UploadGroupId $uploadGroupId = UploadGroupId::DEPARTMENT,
    ): void {
        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/uploader/department/%s', $departmentId),
            [
                'headers' => [
                    'Content-Type' => 'multipart/form-data',
                    'Accept' => 'application/json',
                ],
                'extra' => [
                    'parameters' => [
                        'chunkindex' => '0',
                        'totalchunkcount' => '1',
                        'groupId' => $uploadGroupId->value,
                        'uuid' => 'my-upload-uuid',
                    ],
                    'files' => [
                        'file' => $file,
                    ],
                ],
            ],
        );
    }

    private function getSVGContent(): string
    {
        return <<<'SVG'
            <?xml version="1.0" encoding="utf-8"?>
            <svg fill="#000000" width="800px" height="800px"></svg>
            SVG;
    }
}
