<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\SubType\WooDecisionDocument;

use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Domain\Search\Result\SubType\WooDecisionDocument\DocumentViewModel;
use App\SourceType;
use PHPUnit\Framework\TestCase;

class DocumentViewModelTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $viewmodel = new DocumentViewModel(
            $documentId = '123',
            $documentNr = 'foo-123',
            $filename = 'foo.txt',
            $sourceType = SourceType::PDF,
            $fileUploaded = true,
            $fileSize = 456,
            $pageCount = 12,
            $judgement = Judgement::PUBLIC,
            $date = new \DateTimeImmutable(),
        );

        self::assertEquals($documentId, $viewmodel->documentId);
        self::assertEquals($documentNr, $viewmodel->documentNr);
        self::assertEquals($filename, $viewmodel->fileInfo->getName());
        self::assertEquals($sourceType->value, $viewmodel->fileInfo->getSourceType());
        self::assertEquals($fileUploaded, $viewmodel->fileInfo->isUploaded());
        self::assertEquals($fileSize, $viewmodel->fileInfo->getSize());
        self::assertEquals($pageCount, $viewmodel->pageCount);
        self::assertEquals($judgement, $viewmodel->judgement);
        self::assertEquals($date, $viewmodel->documentDate);
    }
}
