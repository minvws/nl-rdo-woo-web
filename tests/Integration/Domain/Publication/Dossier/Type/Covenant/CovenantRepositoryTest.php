<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\Covenant;

use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use Shared\Domain\Search\Result\Dossier\Covenant\CovenantSearchResult;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class CovenantRepositoryTest extends SharedWebTestCase
{
    public function testGetSearchResultViewModel(): void
    {
        $covenant = CovenantFactory::createOne();
        CovenantAttachmentFactory::createMany(2, [
            'dossier' => $covenant,
        ]);

        $result = self::fromContainer(CovenantRepository::class)
            ->getSearchResultViewModel(
                $covenant->getDocumentPrefix(),
                $covenant->getDossierNr(),
                ApplicationMode::PUBLIC,
            );

        self::assertInstanceOf(CovenantSearchResult::class, $result);
        self::assertEquals(3, $result->documentCount); // 2 attachments + 1 main document = 3
    }
}
