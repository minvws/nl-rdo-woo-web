<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier;

use ReflectionProperty;
use Shared\Domain\Publication\Dossier\DossierBreadcrumbFactory;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;

final class DossierBreadcrumbFactoryTest extends UnitTestCase
{
    private DossierBreadcrumbFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DossierBreadcrumbFactory();
    }

    public function testForDossierReturnsKeyOfType(): void
    {
        self::assertSame('dossier.type.covenant', $this->factory->forDossier(new Covenant()));
    }

    public function testForDossierReturnsKeyOfPublicationReasonForWooDecision(): void
    {
        $wooDecision = new WooDecision();
        $wooDecision->setPublicationReason(PublicationReason::WOB_REQUEST);

        self::assertSame(
            'dossier.type.woo-decision.publication-reason.wob_request',
            $this->factory->forDossier($wooDecision),
        );
    }

    public function testForDossierFallsBackToKeyOfTypeForWooDecisionWithoutPublicationReason(): void
    {
        $wooDecision = new WooDecision();
        new ReflectionProperty(WooDecision::class, 'publicationReason')->setValue($wooDecision, null);

        self::assertSame('dossier.type.woo-decision', $this->factory->forDossier($wooDecision));
    }
}
