<?php

declare(strict_types=1);

namespace App\Tests\Integration\Public\Department;

use App\Domain\Publication\FileInfo;
use App\Domain\Publication\SourceType;
use App\Tests\Factory\DepartmentFactory;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Integration\IntegrationTestTrait;
use App\Tests\Integration\VfsStreamHelpers;
use Doctrine\ORM\EntityManagerInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class DepartmentAssetsControllerTest extends WebTestCase
{
    use IntegrationTestTrait;
    use VfsStreamHelpers;

    private vfsStreamDirectory $root;
    private KernelBrowser $client;
    private string $assetsPath;
    private EntityManagerInterface $doctrine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();

        $this->client = self::createClient();

        $this->assetsPath = self::getContainer()->getParameter('assets_path');
        $this->doctrine = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testDepartmentLogoDownload(): void
    {
        $department = DepartmentFactory::createOne([
            'name' => 'Department of Magic',
        ])->_real();

        $fileInfo = FileInfoFactory::createOne([
            'name' => $name = 'logo.svg',
            'mimetype' => 'image/svg+xml',
            'type' => 'vector-image',
            'sourceType' => SourceType::UNKNOWN,
            'path' => sprintf('department/%s/%s', $department->getId(), $name),
            'size' => strlen($this->getSVGContent()),
        ]);
        $department->setFileInfo($fileInfo);

        $this->doctrine->persist($department);
        $this->doctrine->flush();

        $this->createFileForEntityOnVfs($department, $this->assetsPath, $this->getSVGContent());

        $this->client->request(
            'GET',
            sprintf(
                '/assets/department/%s/logo',
                $department->getId(),
            ),
        );

        self::assertResponseIsSuccessful();

        $this->assertResponseHeaderSame('Content-Type', $department->getFileInfo()->getMimetype() ?? '<unknown>');
        $this->assertResponseHeaderSame('Content-Length', (string) $department->getFileInfo()->getSize());
        $this->assertResponseHeaderSame('Last-Modified', $department->getUpdatedAt()->format('D, d M Y H:i:s') . ' GMT');
    }

    public function testDepartmentLogoDownloadOnNonExistingDepartment(): void
    {
        $this->client->request(
            'GET',
            sprintf(
                '/assets/department/%s/logo.svg',
                Uuid::v6(),
            ),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDepartmentLogoDownloadOnDepartmentWithoutLogo(): void
    {
        $department = DepartmentFactory::createOne([
            'name' => 'Department of Magic',
            'fileInfo' => new FileInfo(),
        ])->_real();

        $this->client->request(
            'GET',
            sprintf(
                '/assets/department/%s/logo.svg',
                $department->getId(),
            ),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function getSVGContent(): string
    {
        return <<<'SVG'
            <?xml version="1.0" encoding="utf-8"?>
            <svg fill="#000000" width="800px" height="800px"></svg>
            SVG;
    }
}
