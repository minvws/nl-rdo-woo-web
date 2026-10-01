<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Updater;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Search\Index\Updater\PageIndexUpdater;
use Shared\Service\Elastic\ElasticClientInterface;
use Shared\Tests\ElasticConfigFactory;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

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
            ElasticConfigFactory::default(),
        );

        parent::setUp();
    }

    public function testUpdate(): void
    {
        $id = 'foo-123';
        $pageNr = 12;
        $content = 'foo bar';

        $this->logger->expects('debug');

        $this->elasticClient->expects('update')->with(Mockery::capture($input));

        $this->indexUpdater->update($id, $pageNr, $content);

        Assert::isArray($input);
        self::assertSame($id, $input['id']);

        $body = $input['body'];
        Assert::isArray($body);
        $script = $body['script'];
        Assert::isArray($script);

        self::assertSame(
            ['page' => ['page_nr' => $pageNr, 'content' => $content]],
            $script['params'],
        );
    }
}
