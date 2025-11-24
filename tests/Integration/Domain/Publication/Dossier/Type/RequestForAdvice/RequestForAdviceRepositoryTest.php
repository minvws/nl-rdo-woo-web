<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\RequestForAdvice;

use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class RequestForAdviceRepositoryTest extends SharedWebTestCase
{
    private function getRepository(): RequestForAdviceRepository
    {
        /** @var RequestForAdviceRepository */
        return self::getContainer()->get(RequestForAdviceRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testGetSearchResultViewModel(): void
    {
        $dossier = RequestForAdviceFactory::createOne();

        $result = $this->getRepository()->getSearchResultViewModel(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
            ApplicationMode::PUBLIC,
        );

        self::assertNotNull($result);
        self::assertEquals($dossier->getDossierNr(), $result->dossierNr);
    }
}
