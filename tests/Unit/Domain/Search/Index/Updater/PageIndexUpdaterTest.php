<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Updater;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Search\Index\Updater\PageIndexUpdater;
use Shared\Service\Elastic\ElasticClientInterface;
use Shared\Tests\Unit\UnitTestCase;

class PageIndexUpdaterTest extends UnitTestCase
{
    private ElasticClientInterface&MockInterface $elasticClient;
    private LoggerInterface&MockInterface $logger;
    private PageIndexUpdater $indexUpdater;

    protected function setUp(): void
    {
        $this->elasticClient = Mockery::mock(ElasticClientInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->indexUpdater = new PageIndexUpdater(
            $this->elasticClient,
            $this->logger,
        );

        parent::setUp();
    }

    public function testUpdate(): void
    {
        $id = 'foo-123';
        $pageNr = 12;
        $content = 'foo bar';

        $this->logger->shouldReceive('debug');

        $this->elasticClient->expects('update')->with(Mockery::on(
            static fn (array $input) => $input['id'] === $id
                && $input['body']['script']['params']['page']['page_nr'] === $pageNr
                && $input['body']['script']['params']['page']['content'] === $content
        ));

        $this->indexUpdater->update($id, $pageNr, $content);
    }
}
