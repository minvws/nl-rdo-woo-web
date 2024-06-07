<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionConfig;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

class WooDecisionConfigTest extends MockeryTestCase
{
    private WooDecisionConfig $config;

    public function setUp(): void
    {
        $this->config = new WooDecisionConfig(
            \Mockery::mock(WorkflowInterface::class),
        );
    }

    public function testCreateInstance(): void
    {
        $dossier = $this->config->createInstance();

        $this->assertInstanceOf(WooDecision::class, $dossier);
        $this->assertNotNull($dossier->getPublicationReason());
    }
}
