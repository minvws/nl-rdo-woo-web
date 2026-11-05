<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer\Mapper;

use Carbon\CarbonImmutable;
use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\WooIndex\Producer\Repository\RawUrlDto;
use Shared\Domain\WooIndex\Producer\Url;
use Shared\ValueObject\Url as UrlValueObject;

final readonly class UrlMapper
{
    public function __construct(
        private PublicUrlGenerator $publicUrlGenerator,
        private DiWooDocumentMapper $diWooDocumentMapper,
    ) {
    }

    public function fromRawUrl(RawUrlDto $rawUrl): Url
    {
        return new Url(
            loc: $this->getLoc($rawUrl),
            lastmod: CarbonImmutable::instance($rawUrl->documentUpdatedAt),
            diWooDocument: $this->diWooDocumentMapper->fromRawUrl($rawUrl),
        );
    }

    private function getLoc(RawUrlDto $rawUrl): UrlValueObject
    {
        return $this->publicUrlGenerator->buildUrlFromRoute(DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD, [
            'documentPrefix' => $rawUrl->documentPrefix,
            'dossierNumber' => $rawUrl->dossierNumber,
            'type' => $rawUrl->source->value,
            'id' => $rawUrl->id,
        ]);
    }
}
