<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use App\Domain\Search\Result\Dossier\Covenant\CovenantSearchResult;
use App\Enum\ApplicationMode;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CovenantRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private function getRepository(): CovenantRepository
    {
        /** @var CovenantRepository */
        return self::getContainer()->get(CovenantRepository::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testGetSearchResultViewModel(): void
    {
        $covenant = CovenantFactory::createOne();
        CovenantAttachmentFactory::createMany(2, [
            'dossier' => $covenant,
        ]);

        $result = $this->getRepository()->getSearchResultViewModel(
            $covenant->getDocumentPrefix(),
            $covenant->getDossierNr(),
            ApplicationMode::PUBLIC,
        );

        self::assertInstanceOf(CovenantSearchResult::class, $result);
        self::assertEquals(3, $result->documentCount); // 2 attachments + 1 main document = 3
    }
}
