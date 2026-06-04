<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class WooDecisionWorkflowTest extends SharedWebTestCase
{
    public function testUpdateMainDocumentIsAllowedForWooDecisionWithPreviewStatus(): void
    {
        $wooDecision = WooDecisionFactory::createOne([
            'status' => DossierStatus::PREVIEW,
        ]);

        $this->assertTrue(
            self::fromContainer(DossierWorkflowManager::class)->isTransitionAllowed($wooDecision, DossierStatusTransition::UPDATE_MAIN_DOCUMENT),
        );
    }
}
