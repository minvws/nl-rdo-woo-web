<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer\Mapper;

use App\Domain\WooIndex\Producer\Repository\RawUrlDto;
use App\Domain\WooIndex\Producer\Url;
use Carbon\CarbonImmutable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class UrlMapper
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private DiWooDocumentMapper $diWooDocumentMapper,
        private string $publicBaseUrl,
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

    private function getLoc(RawUrlDto $rawUrl): string
    {
        $subpath = $this->urlGenerator->generate(
            name: 'app_dossier_file_download',
            parameters: [
                'prefix' => $rawUrl->documentPrefix,
                'dossierId' => $rawUrl->dossierNr,
                'type' => $rawUrl->source->value,
                'id' => $rawUrl->id,
            ],
        );

        return $this->publicBaseUrl . $subpath;
    }
}
