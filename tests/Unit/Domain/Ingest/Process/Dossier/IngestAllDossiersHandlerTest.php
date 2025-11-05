<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Process\Dossier;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Ingest\Process\Dossier\IngestAllDossiersCommand;
use App\Domain\Ingest\Process\Dossier\IngestAllDossiersHandler;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

class IngestAllDossiersHandlerTest extends UnitTestCase
{
    private IngestDispatcher&MockInterface $ingestDispatcher;
    private IngestAllDossiersHandler $handler;

    protected function setUp(): void
    {
        $this->ingestDispatcher = \Mockery::mock(IngestDispatcher::class);

        $this->handler = new IngestAllDossiersHandler(
            $this->ingestDispatcher,
        );
    }

    public function testInvokeSuccessful(): void
    {
        $message = new IngestAllDossiersCommand();

        $this->ingestDispatcher->expects('dispatchIngestDossierCommandForAllDossiers');

        $this->handler->__invoke($message);
    }
}
