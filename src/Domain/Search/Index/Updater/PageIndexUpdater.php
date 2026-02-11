<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Updater;

use Elastic\Elasticsearch\Exception\ServerResponseException;
use Psr\Log\LoggerInterface;
use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Service\Elastic\ElasticClientInterface;

class PageIndexUpdater
{
    use RetryIndexUpdaterTrait;

    public function __construct(
        private readonly ElasticClientInterface $elastic,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ServerResponseException
     */
    public function update(string $id, int $pageNr, string $content): void
    {
        $this->logger->debug('[Elasticsearch] Updating page');
        $this->retry(function () use ($id, $pageNr, $content) {
            $this->elastic->update([
                'index' => ElasticConfig::WRITE_INDEX,
                'id' => $id,
                'body' => [
                    'script' => [
                        'source' => <<< EOF
                                if (ctx._source.pages == null) {
                                    ctx._source.pages = [params.page];
                                } else {
                                    boolean found = false;
                                    for (int i = 0; i < ctx._source.pages.length; ++i) {
                                        if (ctx._source.pages[i].page_nr == params.page.page_nr) {
                                            ctx._source.pages[i] = params.page;
                                            found = true;
                                            break;
                                        }
                                    }
                                    if (found == false) {
                                        ctx._source.pages.add(params.page);
                                    }
                                }
EOF,
                        'lang' => 'painless',
                        'params' => [
                            'page' => [
                                ElasticField::PAGE_NR->value => $pageNr,
                                ElasticField::CONTENT->value => $content,
                            ],
                        ],
                    ],
                ],
            ]);
        });
    }
}
