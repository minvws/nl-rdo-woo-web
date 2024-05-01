<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierDeleteHelper;
use App\Entity\EntityWithFileInfo;
use App\Service\Elastic\ElasticService;
use App\Service\Storage\DocumentStorageService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DossierDeleteHelperTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $doctrine;
    private ElasticService&MockInterface $elastic;
    private DocumentStorageService&MockInterface $documentStorage;
    private AbstractDossier&MockInterface $dossier;
    private DossierDeleteHelper $helper;

    public function setUp(): void
    {
        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->elastic = \Mockery::mock(ElasticService::class);
        $this->documentStorage = \Mockery::mock(DocumentStorageService::class);

        $this->dossier = \Mockery::mock(AbstractDossier::class);

        $this->helper = new DossierDeleteHelper(
            $this->doctrine,
            $this->elastic,
            $this->documentStorage,
        );
    }

    public function testDeleteFromElasticSearch(): void
    {
        $this->elastic->expects('removeDossier')->with($this->dossier);

        $this->helper->deleteFromElasticSearch($this->dossier);
    }

    public function testDeleteForEntitySkipsNullValue(): void
    {
        $this->documentStorage->shouldNotReceive('removeFileForEntity');

        $this->helper->deleteFileForEntity(null);
    }

    public function testDeleteFileForEntity(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);

        $this->documentStorage->expects('removeFileForEntity')->with($entity);

        $this->helper->deleteFileForEntity($entity);
    }

    public function testDelete(): void
    {
        $this->doctrine->expects('remove')->with($this->dossier);
        $this->doctrine->expects('flush');

        $this->helper->delete($this->dossier);
    }

    public function testDeleteAttachments(): void
    {
        $attachmentA = \Mockery::mock(AbstractAttachment::class);
        $attachmentB = \Mockery::mock(AbstractAttachment::class);

        $this->documentStorage->expects('removeFileForEntity')->with($attachmentA);
        $this->documentStorage->expects('removeFileForEntity')->with($attachmentB);

        $this->helper->deleteAttachments(new ArrayCollection([$attachmentA, $attachmentB]));
    }
}
