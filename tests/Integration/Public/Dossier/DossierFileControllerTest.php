<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Public\Dossier;

use Doctrine\ORM\EntityManager;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\InventoryFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\Tests\Integration\VfsStreamHelpers;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

use function sprintf;

final class DossierFileControllerTest extends SharedWebTestCase
{
    use VfsStreamHelpers;

    private vfsStreamDirectory $root;
    private KernelBrowser $client;
    private EntityManager $em;
    private string $documentPathPrefix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();

        $this->client = static::createClient();

        $this->em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->documentPathPrefix = self::getContainer()->getParameter('document_path');
    }

    public function testDownloadingWooDecisionDocument(): void
    {
        /** @var WooDecision $dossier */
        $dossier = WooDecisionFactory::createOne();

        /** @var Document $document */
        $document = DocumentFactory::createOne([
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => true,
                'type' => 'pdf',
            ]),
        ]);

        $dossier->addDocument($document);

        $this->assertDownloadEndpoint(
            $dossier,
            $document,
            DossierFileType::DOCUMENT,
            expectedDownloadFileName: $document->getDocumentNr() . '.pdf',
        );
    }

    public function testDownloadingWooDecisionDocumentUsesOriginalFileType(): void
    {
        /** @var WooDecision $dossier */
        $dossier = WooDecisionFactory::createOne();

        /** @var Document $document */
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
            $dossier,
            $document,
            DossierFileType::DOCUMENT,
            expectedDownloadFileName: $document->getDocumentNr() . '.pdf',
        );
    }

    public function testDownloadingCovenantAttachment(): void
    {
        /** @var Covenant $dossier */
        $dossier = CovenantFactory::createOne();

        /** @var CovenantAttachment $attachment */
        $attachment = CovenantAttachmentFactory::createOne(['dossier' => $dossier]);

        $dossier->addAttachment($attachment);

        $this->assertDownloadEndpoint($dossier, $attachment, DossierFileType::ATTACHMENT);
    }

    public function testDownloadingWooDecisionMainDocument(): void
    {
        /** @var WooDecision $dossier */
        $dossier = WooDecisionFactory::createOne();

        /** @var WooDecisionMainDocument $mainDocument */
        $mainDocument = WooDecisionMainDocumentFactory::createOne(['dossier' => $dossier]);

        $dossier->setMainDocument($mainDocument);

        $this->assertDownloadEndpoint($dossier, $mainDocument, DossierFileType::MAIN_DOCUMENT);
    }

    public function testDownloadingWooDecisionInventory(): void
    {
        /** @var WooDecision $dossier */
        $dossier = WooDecisionFactory::createOne();

        /** @var Inventory $inventory */
        $inventory = InventoryFactory::createOne(['dossier' => $dossier]);

        $dossier->setInventory($inventory);

        $this->assertDownloadEndpoint(
            $dossier,
            $inventory,
            DossierFileType::INVENTORY,
            expectedDisposition: 'attachment',
        );
    }

    private function assertDownloadEndpoint(
        AbstractDossier $dossier,
        EntityWithFileInfo $entityWithFileInfo,
        DossierFileType $dossierFileType,
        ?string $expectedDownloadFileName = null,
        string $expectedDisposition = 'inline',
    ): void {
        $expectedDownloadFileName ??= $entityWithFileInfo->getFileInfo()->getName();

        $this->em->flush();
        $this->em->persist($dossier);

        $this->createFileForEntityOnVfs($entityWithFileInfo, $this->documentPathPrefix);

        $this->client->request(
            'GET',
            sprintf(
                '/dossier/%s/%s/file/download/%s/%s',
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
}
