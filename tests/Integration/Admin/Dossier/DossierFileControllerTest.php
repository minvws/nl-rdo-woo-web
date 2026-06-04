<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Admin\Dossier;

use Doctrine\ORM\EntityManagerInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Security\User;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\InventoryFactory;
use Shared\Tests\Factory\ProductionReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Shared\Tests\Factory\UserFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Webmozart\Assert\Assert;

use function dirname;
use function sprintf;
use function trim;

final class DossierFileControllerTest extends SharedWebTestCase
{
    private vfsStreamDirectory $root;
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private string $documentPathPrefix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();
        $this->client = static::createClient();
        $this->entityManager = self::fromContainer(EntityManagerInterface::class);
        $this->documentPathPrefix = trim(self::getContainer()->getParameter('document_path'), '/');
    }

    public function testAdminDownloadingWooDecisionDocument(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create();

        $dossier = WooDecisionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $document = DocumentFactory::createOne([
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => true,
                'type' => 'pdf',
            ]),
        ]);

        $dossier->addDocument($document);

        $this->assertDownloadEndpoint(
            $user,
            $dossier,
            $document,
            DossierFileType::DOCUMENT,
            expectedDownloadFileName: $document->getDocumentNr() . '.pdf',
        );
    }

    public function testAdminDownloadingWooDecisionDocumentUsesOriginalFileType(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create();

        $dossier = WooDecisionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $document = DocumentFactory::createOne([
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => true,
                'name' => 'foobar.docx',
                'mimetype' => 'application/pdf',
                'type' => 'pdf',
            ]),
        ]);

        $dossier->addDocument($document);

        $this->assertDownloadEndpoint(
            $user,
            $dossier,
            $document,
            DossierFileType::DOCUMENT,
            expectedDownloadFileName: $document->getDocumentNr() . '.pdf',
        );
    }

    public function testAdminDownloadingCovenantAttachment(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create();

        $dossier = CovenantFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        /** @var CovenantAttachment $attachment */
        $attachment = CovenantAttachmentFactory::createOne(['dossier' => $dossier]);

        $dossier->addAttachment($attachment);

        $this->assertDownloadEndpoint($user, $dossier, $attachment, DossierFileType::ATTACHMENT);
    }

    public function testAdminDownloadingWooDecisionMainDocument(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create();

        $dossier = WooDecisionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $mainDocument = WooDecisionMainDocumentFactory::createOne(['dossier' => $dossier]);

        $dossier->setMainDocument($mainDocument);

        $this->assertDownloadEndpoint($user, $dossier, $mainDocument, DossierFileType::MAIN_DOCUMENT);
    }

    public function testAdminDownloadingWooDecisionInventory(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create();

        $dossier = WooDecisionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $inventory = InventoryFactory::createOne(['dossier' => $dossier]);

        $dossier->setInventory($inventory);

        $this->assertDownloadEndpoint(
            $user,
            $dossier,
            $inventory,
            DossierFileType::INVENTORY,
            expectedDisposition: 'attachment',
        );
    }

    public function testAdminDownloadingProductionReport(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create();

        $dossier = WooDecisionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $productionReport = ProductionReportFactory::createOne(['dossier' => $dossier]);

        $dossier->setProductionReport($productionReport);

        $this->assertDownloadEndpoint(
            $user,
            $dossier,
            $productionReport,
            DossierFileType::PRODUCTION_REPORT,
            expectedDisposition: 'attachment',
        );
    }

    private function assertDownloadEndpoint(
        User $user,
        AbstractDossier $dossier,
        EntityWithFileInfo $entityWithFileInfo,
        DossierFileType $dossierFileType,
        ?string $expectedDownloadFileName = null,
        string $expectedDisposition = 'inline',
    ): void {
        $expectedDownloadFileName ??= $entityWithFileInfo->getFileInfo()->getName();

        $this->entityManager->flush();
        $this->entityManager->persist($dossier);

        $this->createFileForEntityOnVfs($entityWithFileInfo);

        $this->client
            ->loginUser($user, 'balie')
            ->request(
                'GET',
                sprintf(
                    '/balie/dossier/%s/%s/file/download/%s/%s',
                    $dossier->getDocumentPrefix(),
                    $dossier->getDossierNr(),
                    $dossierFileType->value,
                    $entityWithFileInfo->getId(),
                ),
            );

        $this->assertResponseIsSuccessful();

        $this->assertResponseHeaderSame('Content-Type', $entityWithFileInfo->getFileInfo()->getMimetype() ?? '');
        $this->assertResponseHeaderSame('Content-Length', (string) $entityWithFileInfo->getFileInfo()->getSize());
        $this->assertResponseHeaderSame('Last-Modified', $entityWithFileInfo->getUpdatedAt()->format('D, d M Y H:i:s') . ' GMT');
        $this->assertResponseHeaderSame('Content-Disposition', sprintf('%s; filename="%s"', $expectedDisposition, $expectedDownloadFileName));
    }

    private function createFileForEntityOnVfs(EntityWithFileInfo $entity): void
    {
        $fileInfoPath = $entity->getFileInfo()->getPath();
        Assert::string($fileInfoPath);

        $newDirectoryPath = sprintf('%s%s', $this->documentPathPrefix, dirname($fileInfoPath));
        vfsStream::newDirectory($newDirectoryPath)->at($this->root);

        /** @var vfsStreamDirectory $childDir */
        $childDir = $this->root->getChild($newDirectoryPath);

        $fileInfoName = $entity->getFileInfo()->getName();
        Assert::string($fileInfoName);

        vfsStream::newFile($fileInfoName)->withContent('This is a test file.')->at($childDir);
    }
}
