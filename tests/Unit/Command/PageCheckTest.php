<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\PageCheck;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Service\Elastic\ElasticService;
use Doctrine\Common\Collections\ArrayCollection;
use MinVWS\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Uuid;

class PageCheckTest extends MockeryTestCase
{
    private Command $command;
    private ElasticService&MockInterface $elasticService;
    private WooDecisionRepository&MockInterface $repository;

    public function setUp(): void
    {
        $this->repository = \Mockery::mock(WooDecisionRepository::class);
        $this->elasticService = \Mockery::mock(ElasticService::class);

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

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->andReturn(Uuid::v6());
        $document->shouldReceive('getFileInfo->getPageCount')->andReturn(1);

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([
            $document,
        ]));

        $this->repository->expects('findAll')->andReturn([$dossier]);

        $esDocument = \Mockery::mock(TypeArray::class);
        $esDocument->expects('getIterable')->with('[_source][pages]')->andReturn([new TypeArray(['page_nr' => 1])]);

        $this->elasticService->expects('getDocument')->andReturn($esDocument);

        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
