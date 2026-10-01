<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\Document;

use Shared\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentDossierFileProvider;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class DocumentDossierFileProviderTest extends SharedWebTestCase
{
    public function testDocumentIsServedForAdminViaTheConceptWooDecision(): void
    {
        $documentDossierFileProvider = self::fromContainer(DocumentDossierFileProvider::class);

        $publishedWooDecision = WooDecisionFactory::new()
            ->published()
            ->create();
        $conceptWooDecision = WooDecisionFactory::new()
            ->concept()
            ->create();

        $document = DocumentFactory::createOne([
            'judgement' => Judgement::PUBLIC,
            'dossiers' => [$publishedWooDecision, $conceptWooDecision],
        ]);

        self::assertSame(
            $document,
            $documentDossierFileProvider->getEntityForAdminUse(
                $conceptWooDecision,
                $document->getId()->toRfc4122(),
            ),
        );
    }

    public function testDocumentIsServedViaThePublishedWooDecision(): void
    {
        $documentDossierFileProvider = self::fromContainer(DocumentDossierFileProvider::class);

        $publishedWooDecision = WooDecisionFactory::new()
            ->published()
            ->create();
        $conceptWooDecision = WooDecisionFactory::new()
            ->concept()
            ->create();

        $document = DocumentFactory::createOne([
            'judgement' => Judgement::PUBLIC,
            'dossiers' => [$publishedWooDecision, $conceptWooDecision],
        ]);

        self::assertSame(
            $document,
            $documentDossierFileProvider->getEntityForPublicUse(
                $publishedWooDecision,
                $document->getId()->toRfc4122(),
            ),
        );
    }

    public function testDocumentNotFoundWhenAllWooDecisionsAreConcept(): void
    {
        $documentDossierFileProvider = self::fromContainer(DocumentDossierFileProvider::class);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::fromContainer(RequestStack::class)->push($request);

        $conceptWooDecisionA = WooDecisionFactory::new()
            ->concept()
            ->create();
        $conceptWooDecisionB = WooDecisionFactory::new()
            ->concept()
            ->create();

        $document = DocumentFactory::createOne([
            'judgement' => Judgement::PUBLIC,
            'dossiers' => [$conceptWooDecisionA, $conceptWooDecisionB],
        ]);

        $this->expectException(DossierFileNotFoundException::class);

        $documentDossierFileProvider->getEntityForPublicUse(
            $conceptWooDecisionA,
            $document->getId()->toRfc4122(),
        );
    }
}
