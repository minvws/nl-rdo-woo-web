<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\Advice;

use Shared\Domain\Publication\Dossier\Type\Advice\AdviceRepository;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Factory\Publication\Dossier\Type\Advice\AdviceFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class AdviceRepositoryTest extends SharedWebTestCase
{
    private function getRepository(): AdviceRepository
    {
        /** @var AdviceRepository */
        return self::getContainer()->get(AdviceRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testGetSearchResultViewModel(): void
    {
        $dossier = AdviceFactory::createOne([
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
