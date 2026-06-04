<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository;

use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileUpdateRepository;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileSetFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\DocumentFileUpdateFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class DocumentFileUpdateRepositoryTest extends SharedWebTestCase
{
    public function testHasUpdateForFileSetAndDocument(): void
    {
        $documentFileSet = DocumentFileSetFactory::createOne();
        $document = DocumentFactory::createOne();
        $documentFileUpdateRepository = self::fromContainer(DocumentFileUpdateRepository::class);

        self::assertFalse($documentFileUpdateRepository->hasUpdateForFileSetAndDocument($documentFileSet, $document));

        $documentFileUpdate = DocumentFileUpdateFactory::createOne([
            'documentFileSet' => $documentFileSet,
            'document' => $document,
        ]);

        $documentFileUpdateRepository->save($documentFileUpdate, true);

        self::assertTrue($documentFileUpdateRepository->hasUpdateForFileSetAndDocument($documentFileSet, $document));
    }
}
