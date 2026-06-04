<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\OtherPublication;

use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\ValueObject\PlainDate;

final class OtherPublicationRepositoryTest extends SharedWebTestCase
{
    public function testGetSearchResultViewModel(): void
    {
        $dossier = OtherPublicationFactory::createOne([
            'dateFrom' => PlainDate::today(),
        ]);

        $result = self::fromContainer(OtherPublicationRepository::class)
            ->getSearchResultViewModel(
                $dossier->getDocumentPrefix(),
                $dossier->getDossierNr(),
                ApplicationMode::PUBLIC,
            );

        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNr(), $result->dossierNr);
    }
}
