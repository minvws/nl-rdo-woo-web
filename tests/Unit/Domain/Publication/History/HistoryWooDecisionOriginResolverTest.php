<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\History;

use Mockery;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Publication\History\History;
use Shared\Domain\Publication\History\HistoryWooDecisionOrigin;
use Shared\Domain\Publication\History\HistoryWooDecisionOriginResolver;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

use function array_keys;

class HistoryWooDecisionOriginResolverTest extends UnitTestCase
{
    public function testResolveLooksUpEachWooDecisionOnlyOnce(): void
    {
        $title = $this->getFaker()->sentence();

        $wooDecision = new WooDecision();
        $wooDecision->setStatus(DossierStatus::PUBLISHED);
        $wooDecision->setTitle(DossierTitle::create($title));

        $wooDecisionId = $wooDecision->getId();

        $firstEntryId = Uuid::v6();
        $firstEntry = Mockery::mock(History::class);
        $firstEntry->expects('getId')->andReturn($firstEntryId);
        $firstEntry->expects('getContext')
            ->twice()
            ->andReturn([History::CONTEXT_ORIGIN_WOO_DECISION_ID => $wooDecisionId->toString()]);

        $secondEntryId = Uuid::v6();
        $secondEntry = Mockery::mock(History::class);
        $secondEntry->expects('getId')->andReturn($secondEntryId);
        $secondEntry->expects('getContext')
            ->twice()
            ->andReturn([History::CONTEXT_ORIGIN_WOO_DECISION_ID => $wooDecisionId->toString()]);

        $thirdEntryId = Uuid::v6();
        $thirdEntry = Mockery::mock(History::class);
        $thirdEntry->expects('getId')->andReturn($thirdEntryId);
        $thirdEntry->expects('getContext')
            ->twice()
            ->andReturn([History::CONTEXT_ORIGIN_WOO_DECISION_ID => $wooDecisionId->toString()]);

        $wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $wooDecisionRepository
            ->expects('findBy')
            ->with(['id' => [$wooDecisionId->toString()]])
            ->andReturn([$wooDecision]);

        $resolver = new HistoryWooDecisionOriginResolver($wooDecisionRepository);

        $origins = $resolver->resolve([$firstEntry, $secondEntry, $thirdEntry]);

        self::assertSame(
            [$firstEntryId->toString(), $secondEntryId->toString(), $thirdEntryId->toString()],
            array_keys($origins),
        );

        $origin = $origins[$firstEntryId->toString()];
        self::assertInstanceOf(HistoryWooDecisionOrigin::class, $origin);
        self::assertEquals($title, $origin->dossierReference->getTitle()->toString());
        self::assertTrue($origin->isPubliclyAvailable);
    }

    public function testConceptWooDecisionIsResolvedAsNotPubliclyAvailable(): void
    {
        $wooDecision = new WooDecision();
        $wooDecision->setStatus(DossierStatus::CONCEPT);
        $wooDecision->setTitle(DossierTitle::create($this->getFaker()->sentence()));

        $entryId = Uuid::v6();

        $entry = Mockery::mock(History::class);
        $entry->expects('getId')->andReturn($entryId);
        $entry->expects('getContext')
            ->twice()
            ->andReturn([History::CONTEXT_ORIGIN_WOO_DECISION_ID => $wooDecision->getId()->toString()]);

        $wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $wooDecisionRepository->expects('findBy')->andReturn([$wooDecision]);

        $resolver = new HistoryWooDecisionOriginResolver($wooDecisionRepository);

        $origins = $resolver->resolve([$entry]);

        $origin = $origins[$entryId->toString()];
        self::assertInstanceOf(HistoryWooDecisionOrigin::class, $origin);
        self::assertFalse($origin->isPubliclyAvailable);
    }

    public function testEntryWithoutAWooDecisionIdResolvesToNullWithoutQuerying(): void
    {
        $entryId = Uuid::v6();

        $entry = Mockery::mock(History::class);
        $entry->expects('getId')->andReturn($entryId);
        $entry->expects('getContext')
            ->twice()
            ->andReturn(['explanation' => $this->getFaker()->word()]);

        $resolver = new HistoryWooDecisionOriginResolver(Mockery::mock(WooDecisionRepository::class));

        self::assertSame([$entryId->toString() => null], $resolver->resolve([$entry]));
    }

    public function testUnknownWooDecisionIdResolvesToNull(): void
    {
        $entryId = Uuid::v6();

        $entry = Mockery::mock(History::class);
        $entry->expects('getId')->andReturn($entryId);
        $entry->expects('getContext')
            ->twice()
            ->andReturn([History::CONTEXT_ORIGIN_WOO_DECISION_ID => Uuid::v6()->toString()]);

        $wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $wooDecisionRepository->expects('findBy')->andReturn([]);

        $resolver = new HistoryWooDecisionOriginResolver($wooDecisionRepository);

        self::assertSame([$entryId->toString() => null], $resolver->resolve([$entry]));
    }
}
