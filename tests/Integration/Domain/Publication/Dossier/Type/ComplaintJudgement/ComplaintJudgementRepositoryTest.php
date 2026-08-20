<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\ComplaintJudgement;

use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementRepository;
use Shared\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\ValueObject\PlainDate;

final class ComplaintJudgementRepositoryTest extends SharedWebTestCase
{
    public function testGetSearchResultViewModel(): void
    {
        $dossier = ComplaintJudgementFactory::createOne([
            'dateFrom' => PlainDate::today(),
        ]);

        $result = self::fromContainer(ComplaintJudgementRepository::class)
            ->getSearchResultViewModel(
                $dossier->getDocumentPrefix(),
                $dossier->getDossierNumber(),
                ApplicationId::PUBLIC,
            );

        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNumber(), $result->dossierNumber);
    }
}
