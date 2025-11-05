<?php

declare(strict_types=1);

namespace App\Api\Publication\V1;

final readonly class PublicationV1Api
{
    public const API_NAME = 'Publication V1';
    public const API_TAG = 'publication-v1';
    public const API_PREFIX = '/api/publication/v1';
    public const API_NAMESPACE_PREFIX = 'App\Api\Publication\V1';

    public const OPENAPI_ROUTE_NAME = 'api_publication_v1_open_api_json';
    public const OPENAPI_URLS = [
        '/documentatie/api/publication/v1/openapi.json',
    ];

    public const DOCS_ROUTE_NAME = 'api_publication_v1_docs';
    public const DOCS_URLS = [
        '/documentatie/api/publication/v1/docs',
        '/documentatie/api/publication/v1/openapi',
    ];
}
