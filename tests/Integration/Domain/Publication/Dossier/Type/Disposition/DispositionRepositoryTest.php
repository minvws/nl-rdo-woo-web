<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\Disposition;

use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class DispositionRepositoryTest extends SharedWebTestCase
{
    private function getRepository(): DispositionRepository
    {
        /** @var DispositionRepository */
        return self::getContainer()->get(DispositionRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testGetSearchResultViewModel(): void
    {
        $dossier = DispositionFactory::createOne([
            'dateFrom' => new \DateTimeImmutable(),
        ]);

        $result = $this->getRepository()->getSearchResultViewModel(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
            ApplicationMode::PUBLIC,
        );

        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNr(), $result->dossierNr);
    }
}
