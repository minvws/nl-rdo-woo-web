<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Mockery;
use Shared\Command\Where;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Uid\Uuid;

use function parse_url;
use function sprintf;

use const PHP_URL_PATH;

class WhereTest extends UnitTestCase
{
    public function testExecuteWithInvalidUrl(): void
    {
        $url = $this->getFaker()->url();

        $wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $documentRepository = Mockery::mock(DocumentRepository::class);
        $matcher = Mockery::mock(UrlMatcherInterface::class);
        $matcher->expects('match')->with(parse_url($url, PHP_URL_PATH))->andThrow(NoConfigurationException::class);

        $command = new Where($wooDecisionRepository, $documentRepository, $matcher);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'url' => $url,
        ]);

        self::assertEquals(Command::FAILURE, $commandTester->getStatusCode());
    }

    public function testExecuteMatchesDocumentByDocumentNumber(): void
    {
        $documentId = Uuid::v6();
        $dossierNumber = 'tst-123';
        $documentNumber = 'PREF-matter-123';

        $matcher = Mockery::mock(UrlMatcherInterface::class);
        $matcher->expects('match')->andReturn([
            '_route' => 'app_document_detail',
            'dossierNumber' => $dossierNumber,
            'documentNumber' => $documentNumber,
        ]);

        $wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $wooDecisionRepository->expects('findOneBy')
            ->with(['dossierNumber' => $dossierNumber])
            ->andReturn(Mockery::mock(WooDecision::class));

        $document = Mockery::mock(Document::class);
        $document->expects('getId')->andReturn($documentId);
        $document->expects('getFileInfo->getName')->andReturn('file.pdf');
        $document->expects('getFileInfo->getPath')->andReturn('/path/file.pdf');

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('findOneBy')
            ->with(['documentNumber' => $documentNumber])
            ->andReturn($document);

        $command = new Where($wooDecisionRepository, $documentRepository, $matcher);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'url' => sprintf('https://example.com/dossier/PREF/%s/document/%s', $dossierNumber, $documentNumber),
        ]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertStringContainsString($documentId->toRfc4122(), $commandTester->getDisplay());
    }
}
