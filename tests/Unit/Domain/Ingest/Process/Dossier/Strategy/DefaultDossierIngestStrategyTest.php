<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Process\Dossier\Strategy;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Ingest\Process\Dossier\Strategy\DefaultDossierIngestStrategy;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class DefaultDossierIngestStrategyTest extends UnitTestCase
{
    private SearchDispatcher&MockInterface $searchDispatcher;
    private DefaultDossierIngestStrategy $ingester;

    protected function setUp(): void
    {
        $this->searchDispatcher = Mockery::mock(SearchDispatcher::class);

        $this->ingester = new DefaultDossierIngestStrategy(
            $this->searchDispatcher,
        );
    }

    public function testIngest(): void
    {
        $mainDocumentId = Uuid::v6();
        $mainDocument = Mockery::mock(CovenantMainDocument::class);
        $mainDocument->expects('getId')->andReturn($mainDocumentId);

        $attachmentIdA = Uuid::v6();
        $attachmentA = Mockery::mock(CovenantAttachment::class);
        $attachmentA->expects('getId')->andReturn($attachmentIdA);
        $attachmentA->expects('isWithdrawn')->andReturnFalse();

        // This attachment is withdrawn and should NOT be indexed
        $attachmentB = Mockery::mock(CovenantAttachment::class);
        $attachmentB->expects('isWithdrawn')->andReturnTrue();

        $dossierId = Uuid::v6();
        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->andReturn($dossierId);
        $dossier->expects('getAttachments')->andReturn(new ArrayCollection([$attachmentA, $attachmentB]));
        $dossier->expects('getMainDocument')->times(2)->andReturn($mainDocument);

        $this->searchDispatcher->expects('dispatchIndexDossierCommand')->with($dossierId, false);
        $this->searchDispatcher->expects('dispatchIndexMainDocumentCommand')->with($mainDocumentId);
        $this->searchDispatcher->expects('dispatchIndexAttachmentCommand')->with($attachmentIdA);

        $this->ingester->ingest($dossier, false);
    }
}
