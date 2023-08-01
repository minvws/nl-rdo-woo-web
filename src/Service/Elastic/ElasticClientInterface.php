<?php

declare(strict_types=1);

namespace App\Service\Elastic;

use Elastic\Elasticsearch\Endpoints\Cat;
use Elastic\Elasticsearch\Endpoints\Indices;

/**
 * This interface is needed since we do not use the ElasticSearch\Client directly, but decorated this in a wrapper to enable
 * logging and profiling. Since the Client cannot be extended, we need to use an interface to typehint the wrapper.
 */
interface ElasticClientInterface
{
    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function search(array $params = []);

    public function cat(): Cat;

    public function indices(): Indices;

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function count(array $params = []);

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function update(array $params = []);

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function exists(array $params = []);

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function updateByQuery(array $params = []);

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function get(array $params = []);

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function create(array $params = []);

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function close(array $params = []);

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function putSettings(array $params = []);

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function putMapping(array $params = []);

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function open(array $params = []);

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function delete(array $params = []);

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function deleteAlias(array $params = []);

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function putAlias(array $params = []);

    /**
     * @param mixed[] $params
     *
     * @return \Elastic\Elasticsearch\Response\Elasticsearch|\Http\Promise\Promise
     */
    public function updateAliases(array $params = []);
}
