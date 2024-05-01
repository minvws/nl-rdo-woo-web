<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\DossierDeleteHelper;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDeleteStrategy;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Webmozart\Assert\InvalidArgumentException;

class CovenantDeleteStrategyTest extends MockeryTestCase
{
    private DossierDeleteHelper&MockInterface $deleteHelper;
    private CovenantDeleteStrategy $strategy;

    public function setUp(): void
    {
        $this->deleteHelper = \Mockery::mock(DossierDeleteHelper::class);

        $this->strategy = new CovenantDeleteStrategy(
            $this->deleteHelper,
        );
    }

    public function testDeleteThrowsExceptionForInvalidType(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);

        $this->expectException(InvalidArgumentException::class);

        $this->strategy->delete($dossier);
    }

    public function testDelete(): void
    {
        $dossier = \Mockery::mock(Covenant::class);

        $document = \Mockery::mock(CovenantDocument::class);
        $dossier->shouldReceive('getDocument')->andReturn($document);

        $attachments = new ArrayCollection([\Mockery::mock(CovenantAttachment::class)]);
        $dossier->shouldReceive('getAttachments')->andReturn($attachments);

        $this->deleteHelper->expects('deleteFileForEntity')->with($document);
        $this->deleteHelper->expects('deleteAttachments')->with($attachments);

        $this->strategy->delete($dossier);
    }
}
