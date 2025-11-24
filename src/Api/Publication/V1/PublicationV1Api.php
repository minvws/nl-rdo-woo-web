<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1;

final readonly class PublicationV1Api
{
    public const string API_NAME = 'Publication V1';
    public const string API_TAG = 'publication-v1';
    public const string API_PREFIX = '/api/publication/v1';
    public const string API_NAMESPACE_PREFIX = 'Shared\Api\Publication\V1';

    public const string OPENAPI_ROUTE_NAME = 'api_publication_v1_open_api_json';
    public const array OPENAPI_URLS = [
        '/documentatie/api/publication/v1/openapi.json',
    ];

    public const string DOCS_ROUTE_NAME = 'api_publication_v1_docs';
    public const array DOCS_URLS = [
        '/documentatie/api/publication/v1/docs',
        '/documentatie/api/publication/v1/openapi',
    ];
}
