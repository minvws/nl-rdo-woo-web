<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\AttachmentDeleteStrategy;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Service\Storage\DocumentStorageService;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class AttachmentDeleteStrategyTest extends MockeryTestCase
{
    private DocumentStorageService&MockInterface $documentStorage;
    private AttachmentDeleteStrategy $strategy;

    public function setUp(): void
    {
        $this->documentStorage = \Mockery::mock(DocumentStorageService::class);
        $this->strategy = new AttachmentDeleteStrategy($this->documentStorage);

        parent::setUp();
    }

    public function testDeleteReturnsEarlyWhenDossierHasNoAttachments(): void
    {
        $this->documentStorage->shouldNotHaveBeenCalled();
        $dossier = \Mockery::mock(AbstractDossier::class);

        $this->strategy->delete($dossier);
    }

    public function testDeleteAttachments(): void
    {
        $attachmentA = \Mockery::mock(CovenantAttachment::class);
        $attachmentB = \Mockery::mock(CovenantAttachment::class);

        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getAttachments')->andReturn(new ArrayCollection([
            $attachmentA,
            $attachmentB,
        ]));

        $this->documentStorage->expects('removeFileForEntity')->with($attachmentA);
        $this->documentStorage->expects('removeFileForEntity')->with($attachmentB);

        $this->strategy->delete($dossier);
    }
}
