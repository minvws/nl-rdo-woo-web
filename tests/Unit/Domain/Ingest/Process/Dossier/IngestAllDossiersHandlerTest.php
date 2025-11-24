<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Process\Dossier;

use Mockery\MockInterface;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Ingest\Process\Dossier\IngestAllDossiersCommand;
use Shared\Domain\Ingest\Process\Dossier\IngestAllDossiersHandler;
use Shared\Tests\Unit\UnitTestCase;

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
