<?php

declare(strict_types=1);

namespace App\Service\Elastic;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\ClientInterface;
use GuzzleHttp\Client;

/**
 * Creates a configured Elasticsearch client.
 */
class ElasticClientFactory
{
    public static function create(
        string $host,
        string $username = null,
        string $password = null,
        string $mtlsCertPath = null,
        string $mtlsKeyPath = null,
        string $mtlsCAPath = null
    ): ClientInterface {
        $builder = new ClientBuilder();
        $builder->setHttpClient(new Client([
            'timeout' => 15,
            'connect_timeout' => 5,
        ]));
        $builder->setHosts(explode(',', $host));

        if (! empty($username)) {
            $builder->setBasicAuthentication($username, $password ?? '');
        }

        if (! empty($mtlsCertPath) && ! empty($mtlsKeyPath)) {
            $builder->setSSLCert($mtlsCertPath);
            $builder->setSSLKey($mtlsKeyPath);
        }

        if (! empty($mtlsCAPath)) {
            $builder->setCABundle($mtlsCAPath);
        }

        return $builder->build();
    }
}
