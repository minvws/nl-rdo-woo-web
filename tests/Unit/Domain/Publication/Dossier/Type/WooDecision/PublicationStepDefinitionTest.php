<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Steps\PublicationStepDefinition;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class PublicationStepDefinitionTest extends MockeryTestCase
{
    public function testIsCompletedReturnsTrueWhenDossierStatusIsPublished(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $step = new PublicationStepDefinition();

        self::assertTrue($step->isCompleted($dossier));
    }

    public function testIsCompletedReturnsTrueWhenDossierHasAPublicationDateInTheFuture(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);
        $dossier->shouldReceive('hasFuturePublicationDate')->andReturnTrue();

        $step = new PublicationStepDefinition();

        self::assertTrue($step->isCompleted($dossier));
    }

    public function testIsCompletedReturnsFalseWhenDossierIsNotPublishedAndNotPlanned(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);
        $dossier->shouldReceive('hasFuturePublicationDate')->andReturnFalse();

        $step = new PublicationStepDefinition();

        self::assertFalse($step->isCompleted($dossier));
    }
}
