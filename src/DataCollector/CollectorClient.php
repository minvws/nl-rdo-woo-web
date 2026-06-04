<?php

declare(strict_types=1);

namespace Shared\DataCollector;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientInterface;
use Elastic\Elasticsearch\Endpoints\Cat;
use Elastic\Elasticsearch\Endpoints\Indices;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Elastic\Transport\Transport;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Service\Elastic\ElasticClientInterface;
use Webmozart\Assert\Assert;

class CollectorClient implements ClientInterface, ElasticClientInterface
{
    public function __construct(
        protected Client $elastic,
        protected ElasticCollector $collector,
    ) {
    }

    public function getTransport(): Transport
    {
        return $this->elastic->getTransport();
    }

    public function getLogger(): LoggerInterface
    {
        return $this->elastic->getLogger();
    }

    public function setAsync(bool $async): ClientInterface
    {
        throw new RuntimeException('async is not supported');
    }

    public function getAsync(): bool
    {
        return false;
    }

    public function setElasticMetaHeader(bool $active): ClientInterface
    {
        return $this->elastic->setElasticMetaHeader($active);
    }

    public function getElasticMetaHeader(): bool
    {
        return $this->elastic->getElasticMetaHeader();
    }

    public function setResponseException(bool $active): ClientInterface
    {
        return $this->elastic->setResponseException($active);
    }

    public function getResponseException(): bool
    {
        return $this->elastic->getResponseException();
    }

    public function sendRequest(RequestInterface $request): Elasticsearch
    {
        $response = $this->elastic->sendRequest($request);
        Assert::isInstanceOf($response, Elasticsearch::class);

        return $response;
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    protected function collectData(string $method, array $arguments, string $type = 'array'): Elasticsearch
    {
        $response = $this->elastic->$method($arguments);
        Assert::isInstanceOf($response, Elasticsearch::class);

        $this->collector->addCall($method, $arguments, $response, $type);

        return $response;
    }

    public function search(array $params = []): Elasticsearch
    {
        return $this->collectData('search', $params);
    }

    public function cat(): Cat
    {
        return $this->elastic->cat();
    }

    public function indices(): Indices
    {
        return $this->elastic->indices();
    }

    /**
     * @param array{
     *      index?: string|array<array-key, string>,
     *      ignore_unavailable?: bool,
     *      ignore_throttled?: bool,
     *      allow_no_indices?: bool,
     *      expand_wildcards?: string,
     *      min_score?: float,
     *      preference?: string,
     *      project_routing?: string,
     *      routing?: string|array<array-key, string>,
     *      q?: string,
     *      analyzer?: string,
     *      analyze_wildcard?: bool,
     *      default_operator?: string,
     *      df?: string,
     *      lenient?: bool,
     *      terminate_after?: int,
     *      pretty?: bool,
     *      human?: bool,
     *      error_trace?: bool,
     *      source?: string,
     *      filter_path?: string|array<array-key, string>,
     *      body?: string|array<array-key, mixed>
     * } $params
     */
    public function count(array $params = []): Elasticsearch
    {
        $response = $this->elastic->count($params);
        Assert::isInstanceOf($response, Elasticsearch::class);

        return $response;
    }

    public function update(array $params = []): Elasticsearch
    {
        return $this->collectData('update', $params);
    }

    public function exists(array $params = []): Elasticsearch
    {
        return $this->collectData('exists', $params, 'bool');
    }

    public function updateByQuery(array $params = []): Elasticsearch
    {
        return $this->collectData('updateByQuery', $params);
    }

    public function get(array $params = []): Elasticsearch
    {
        return $this->collectData('get', $params);
    }

    public function create(array $params = []): Elasticsearch
    {
        return $this->collectData('create', $params);
    }

    public function close(array $params = []): Elasticsearch
    {
        return $this->collectData('close', $params, 'bool');
    }

    public function putSettings(array $params = []): Elasticsearch
    {
        return $this->collectData('putSettings', $params);
    }

    public function putMapping(array $params = []): Elasticsearch
    {
        return $this->collectData('putMapping', $params);
    }

    public function open(array $params = []): Elasticsearch
    {
        return $this->collectData('open', $params);
    }

    public function delete(array $params = []): Elasticsearch
    {
        return $this->collectData('delete', $params);
    }

    public function deleteAlias(array $params = []): Elasticsearch
    {
        return $this->collectData('deleteAlias', $params);
    }

    public function putAlias(array $params = []): Elasticsearch
    {
        return $this->collectData('putAlias', $params);
    }

    public function updateAliases(array $params = []): Elasticsearch
    {
        return $this->collectData('updateAlias', $params);
    }

    public function setServerless(bool $value): self
    {
        $this->elastic->setServerless($value);

        return $this;
    }

    public function getServerless(): bool
    {
        return $this->elastic->getServerless();
    }
}
