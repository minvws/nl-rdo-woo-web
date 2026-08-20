<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\DraftDecision;

use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionRepository;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class DraftDecisionRepositoryTest extends SharedWebTestCase
{
    public function testGetSearchResultViewModel(): void
    {
        $dossier = DraftDecisionFactory::createOne();

        $result = self::fromContainer(DraftDecisionRepository::class)
            ->getSearchResultViewModel(
                $dossier->getDocumentPrefix(),
                $dossier->getDossierNumber(),
                ApplicationId::PUBLIC,
            );

        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNumber(), $result->dossierNumber);
    }
}
