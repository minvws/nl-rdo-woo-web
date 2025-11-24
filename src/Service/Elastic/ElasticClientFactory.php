<?php

declare(strict_types=1);

namespace Shared\Service\Elastic;

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
        ?string $username = null,
        ?string $password = null,
        ?string $mtlsCertPath = null,
        ?string $mtlsKeyPath = null,
        ?string $mtlsCAPath = null,
    ): ClientInterface {
        $builder = new ClientBuilder();
        $builder->setHttpClient(new Client([
            'timeout' => 15,
            'connect_timeout' => 5,
        ]));
        $builder->setHosts(explode(',', $host));

        if ($username !== null && $username !== '') {
            $builder->setBasicAuthentication($username, $password ?? '');
        }

        if ($mtlsCertPath !== null && $mtlsCertPath !== '' && $mtlsKeyPath !== null && $mtlsKeyPath !== '') {
            $builder->setSSLCert($mtlsCertPath);
            $builder->setSSLKey($mtlsKeyPath);
        }

        if ($mtlsCAPath !== null && $mtlsCAPath !== '') {
            $builder->setCABundle($mtlsCAPath);
        }

        return $builder->build();
    }
}
