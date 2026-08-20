<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Workflow;

use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflow;
use Shared\Tests\Unit\UnitTestCase;

class DossierWorkflowTest extends UnitTestCase
{
    public function testDossierWorkflow(): void
    {
        $this->assertMatchesSnapshot(DossierWorkflow::cases());
    }

    public function testGetAllWorkflows(): void
    {
        $this->assertMatchesSnapshot(DossierWorkflow::all());
    }

    public function testGetWorkflowConfigs(): void
    {
        $this->assertMatchesSnapshot(DossierWorkflow::getConfigs());
    }
}
