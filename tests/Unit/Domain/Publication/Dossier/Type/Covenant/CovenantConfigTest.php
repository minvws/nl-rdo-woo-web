<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantConfig;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

class CovenantConfigTest extends MockeryTestCase
{
    private CovenantConfig $config;

    public function setUp(): void
    {
        $this->config = new CovenantConfig(
            \Mockery::mock(WorkflowInterface::class),
        );
    }

    public function testCreateInstance(): void
    {
        $dossier = $this->config->createInstance();

        $this->assertInstanceOf(Covenant::class, $dossier);
    }
}
