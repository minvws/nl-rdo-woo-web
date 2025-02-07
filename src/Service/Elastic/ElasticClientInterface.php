<?php

declare(strict_types=1);

namespace App\Service\Elastic;

use Elastic\Elasticsearch\Endpoints\Cat;
use Elastic\Elasticsearch\Endpoints\Indices;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;

/**
 * This interface is needed since we do not use the ElasticSearch\Client directly, but decorated this in a wrapper to enable
 * logging and profiling. Since the Client cannot be extended, we need to use an interface to typehint the wrapper.
 */
interface ElasticClientInterface
{
    /**
     * @param mixed[] $params
     */
    public function search(array $params = []): Elasticsearch|Promise;

    public function cat(): Cat;

    public function indices(): Indices;

    /**
     * @param mixed[] $params
     */
    public function count(array $params = []): Elasticsearch|Promise;

    /**
     * @param mixed[] $params
     */
    public function update(array $params = []): Elasticsearch|Promise;

    /**
     * @param mixed[] $params
     */
    public function exists(array $params = []): Elasticsearch|Promise;

    /**
     * @param mixed[] $params
     */
    public function updateByQuery(array $params = []): Elasticsearch|Promise;

    /**
     * @param mixed[] $params
     */
    public function get(array $params = []): Elasticsearch|Promise;

    /**
     * @param mixed[] $params
     */
    public function create(array $params = []): Elasticsearch|Promise;

    /**
     * @param mixed[] $params
     */
    public function close(array $params = []): Elasticsearch|Promise;

    /**
     * @param mixed[] $params
     */
    public function putSettings(array $params = []): Elasticsearch|Promise;

    /**
     * @param mixed[] $params
     */
    public function putMapping(array $params = []): Elasticsearch|Promise;

    /**
     * @param mixed[] $params
     */
    public function open(array $params = []): Elasticsearch|Promise;

    /**
     * @param mixed[] $params
     */
    public function delete(array $params = []): Elasticsearch|Promise;

    /**
     * @param mixed[] $params
     */
    public function deleteAlias(array $params = []): Elasticsearch|Promise;

    /**
     * @param mixed[] $params
     */
    public function putAlias(array $params = []): Elasticsearch|Promise;

    /**
     * @param mixed[] $params
     */
    public function updateAliases(array $params = []): Elasticsearch|Promise;
}
