<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WooDecisionWorkflowTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    private function getWorkflow(): DossierWorkflowManager
    {
        /** @var DossierWorkflowManager */
        return self::getContainer()->get(DossierWorkflowManager::class);
    }

    public function testUpdateMainDocumentIsAllowedForWooDecisionWithPreviewStatus(): void
    {
        $wooDecision = WooDecisionFactory::createOne([
            'status' => DossierStatus::PREVIEW,
        ]);

        $this->assertTrue(
            $this->getWorkflow()->isTransitionAllowed($wooDecision, DossierStatusTransition::UPDATE_MAIN_DOCUMENT),
        );
    }
}
