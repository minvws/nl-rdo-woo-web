<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Doctrine\Common\Collections\ArrayCollection;
use MinVWS\TypeArray\TypeArray;
use Mockery;
use Mockery\MockInterface;
use Shared\Command\PageCheck;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\Elastic\ElasticService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Uuid;

class PageCheckTest extends UnitTestCase
{
    private Command $command;
    private ElasticService&MockInterface $elasticService;
    private WooDecisionRepository&MockInterface $repository;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(WooDecisionRepository::class);
        $this->elasticService = Mockery::mock(ElasticService::class);

        $application = new Application();
        $application->add(
            new PageCheck(
                $this->repository,
                $this->elasticService,
            ),
        );

        $this->command = $application->find('woopie:page:check');
    }

    public function testExecuteHappyFlow(): void
    {
        $commandTester = new CommandTester($this->command);

        $document = Mockery::mock(Document::class);
        $document->expects('getId')
            ->andReturn(Uuid::v6());
        $document->expects('getFileInfo->getPageCount')
            ->twice()
            ->andReturn(1);

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDocuments')
            ->andReturn(new ArrayCollection([
                $document,
            ]));

        $this->repository->expects('findAll')->andReturn([$dossier]);

        $esDocument = Mockery::mock(TypeArray::class);
        $esDocument->expects('getIterable')
            ->with('[_source][pages]')
            ->andReturn([new TypeArray(['page_nr' => 1])]);

        $this->elasticService->expects('getDocument')->andReturn($esDocument);

        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
