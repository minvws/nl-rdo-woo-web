<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\RequestForAdvice;

use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class RequestForAdviceRepositoryTest extends SharedWebTestCase
{
    public function testGetSearchResultViewModel(): void
    {
        $dossier = RequestForAdviceFactory::createOne();

        $result = self::fromContainer(RequestForAdviceRepository::class)
            ->getSearchResultViewModel(
                $dossier->getDocumentPrefix(),
                $dossier->getDossierNumber(),
                ApplicationId::PUBLIC,
            );

        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNumber(), $result->dossierNumber);
    }
}
