<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Updater;

use Elastic\Elasticsearch\Exception\ServerResponseException;
use Psr\Log\LoggerInterface;
use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Index\Schema\ElasticNestedField;
use Shared\Service\Elastic\ElasticClientInterface;

class PageIndexUpdater
{
    use RetryIndexUpdaterTrait;

    private const string PARAMETER_NAME = 'page';

    public function __construct(
        private readonly ElasticClientInterface $elastic,
        private LoggerInterface $logger,
        private ElasticConfig $elasticConfig,
    ) {
    }

    /**
     * @throws ServerResponseException
     */
    public function update(string $id, int $pageNr, string $content): void
    {
        $this->logger->debug('[Elasticsearch] Updating page');
        $parameterName = self::PARAMETER_NAME;
        $pagesField = ElasticNestedField::PAGES->value;
        $pageNrField = ElasticField::PAGE_NR->value;

        $this->retry(function () use ($id, $pageNr, $content, $parameterName, $pagesField, $pageNrField) {
            $this->elastic->update([
                'index' => $this->elasticConfig->writeIndex,
                'id' => $id,
                'body' => [
                    'script' => [
                        'source' => <<< EOF
                                if (ctx._source.{$pagesField} == null) {
                                    ctx._source.{$pagesField} = [params.{$parameterName}];
                                } else {
                                    boolean found = false;
                                    for (int i = 0; i < ctx._source.{$pagesField}.length; ++i) {
                                        if (ctx._source.{$pagesField}[i].{$pageNrField} == params.{$parameterName}.{$pageNrField}) {
                                            ctx._source.{$pagesField}[i] = params.{$parameterName};
                                            found = true;
                                            break;
                                        }
                                    }
                                    if (found == false) {
                                        ctx._source.{$pagesField}.add(params.{$parameterName});
                                    }
                                }
EOF,
                        'lang' => 'painless',
                        'params' => [
                            $parameterName => [
                                $pageNrField => $pageNr,
                                ElasticField::CONTENT->value => $content,
                            ],
                        ],
                    ],
                ],
            ]);
        });
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
