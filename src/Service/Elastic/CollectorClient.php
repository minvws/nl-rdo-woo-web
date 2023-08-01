<?php

declare(strict_types=1);

namespace App\Service\Elastic;

use App\DataCollector\ElasticCollector;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientInterface;
use Elastic\Elasticsearch\Endpoints\Cat;
use Elastic\Elasticsearch\Endpoints\Indices;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Elastic\Transport\Transport;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CollectorClient implements ClientInterface, ElasticClientInterface
{
    protected Client $elastic;
    protected ElasticCollector $collector;

    public function __construct(Client $elastic, ElasticCollector $collector)
    {
        $this->elastic = $elastic;
        $this->collector = $collector;
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
        return $this->elastic->setAsync($async);
    }

    public function getAsync(): bool
    {
        return $this->elastic->getAsync();
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

    /**
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function sendRequest(RequestInterface $request)
    {
        return $this->elastic->sendRequest($request);
    }

    /**
     * @param mixed[] $arguments
     *
     * @return Elasticsearch|Promise
     */
    protected function collectData(string $method, array $arguments, string $type = 'array')
    {
        $response = $this->elastic->$method($arguments);

        $this->collector->addCall($method, $arguments, $response, $type);

        /** @var ElasticSearch|Promise $response */
        return $response;
    }

    public function search(array $params = [])
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

    public function count(array $params = [])
    {
        /** @var ElasticSearch|Promise $response */
        $response = $this->elastic->count($params);

        return $response;
    }

    public function update(array $params = [])
    {
        return $this->collectData('update', $params);
    }

    public function exists(array $params = [])
    {
        return $this->collectData('exists', $params, 'bool');
    }

    public function updateByQuery(array $params = [])
    {
        return $this->collectData('updateByQuery', $params);
    }

    public function get(array $params = [])
    {
        return $this->collectData('get', $params);
    }

    public function create(array $params = [])
    {
        return $this->collectData('create', $params);
    }

    public function close(array $params = [])
    {
        return $this->collectData('close', $params, 'bool');
    }

    public function putSettings(array $params = [])
    {
        return $this->collectData('putSettings', $params);
    }

    public function putMapping(array $params = [])
    {
        return $this->collectData('putMapping', $params);
    }

    public function open(array $params = [])
    {
        return $this->collectData('open', $params);
    }

    public function delete(array $params = [])
    {
        return $this->collectData('delete', $params);
    }

    public function deleteAlias(array $params = [])
    {
        return $this->collectData('deleteAlias', $params);
    }

    public function putAlias(array $params = [])
    {
        return $this->collectData('putAlias', $params);
    }

    public function updateAliases(array $params = [])
    {
        return $this->collectData('updateAlias', $params);
    }
}
