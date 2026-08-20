<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\Disposition;

use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\ValueObject\PlainDate;

final class DispositionRepositoryTest extends SharedWebTestCase
{
    public function testGetSearchResultViewModel(): void
    {
        $dossier = DispositionFactory::createOne([
            'dateFrom' => PlainDate::today(),
        ]);

        $result = self::fromContainer(DispositionRepository::class)
            ->getSearchResultViewModel(
                $dossier->getDocumentPrefix(),
                $dossier->getDossierNumber(),
                ApplicationId::PUBLIC,
            );

        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNumber(), $result->dossierNumber);
    }
}
