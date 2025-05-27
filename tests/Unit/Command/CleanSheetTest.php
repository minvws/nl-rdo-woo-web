<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\CleanSheet;
use App\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use App\Domain\WooIndex\WooIndexSitemapService;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CleanSheetTest extends MockeryTestCase
{
    private Command $command;
    private ElasticIndexManager&MockInterface $indexService;
    private EntityManagerInterface&MockInterface $entityManager;
    private HttpClientInterface&MockInterface $client;
    private WooIndexSitemapService&MockInterface $wooIndexSitemapService;
    private Command&MockInterface $cacheClearCommand;

    public function setUp(): void
    {
        $this->indexService = \Mockery::mock(ElasticIndexManager::class);
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->client = \Mockery::mock(HttpClientInterface::class);
        $this->wooIndexSitemapService = \Mockery::mock(WooIndexSitemapService::class);

        $helperSet = \Mockery::mock(HelperSet::class);
        $helperSet->shouldReceive('getIterator')->andReturn(new \ArrayIterator());

        $this->cacheClearCommand = \Mockery::mock(Command::class);
        $this->cacheClearCommand->shouldReceive('setApplication');
        $this->cacheClearCommand->shouldReceive('isEnabled')->andReturnTrue();
        $this->cacheClearCommand->shouldReceive('getDefinition')->andReturn(new InputDefinition());
        $this->cacheClearCommand->shouldReceive('getName')->andReturn('cache:pool:clear');
        $this->cacheClearCommand->shouldReceive('getAliases')->andReturn([]);
        $this->cacheClearCommand->shouldReceive('getHelperSet')->andReturn($helperSet);

        $application = new Application();
        $application->add(
            new CleanSheet(
                ['dummy-dsn'],
                $this->entityManager,
                $this->indexService,
                $this->client,
                $this->wooIndexSitemapService,
            ),
        );
        $application->add($this->cacheClearCommand);

        $this->command = $application->find('woopie:dev:clean-sheet');
    }

    public function testExecuteHappyFlow(): void
    {
        $commandTester = new CommandTester($this->command);

        $this->indexService->expects('delete')->with('woopie');
        $this->indexService->expects('createLatestWithAliases')->with('woopie');

        $this->wooIndexSitemapService->shouldReceive('cleanupAllSitemaps')->once();

        $this->cacheClearCommand->expects('run');

        $commandTester->execute(['--force' => 1]);

        self::assertEquals(0, $commandTester->getStatusCode());
    }
}
