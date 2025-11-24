<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer\Mapper;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use Shared\Domain\WooIndex\Producer\Repository\RawUrlDto;
use Shared\Domain\WooIndex\Producer\UrlReference;
use Webmozart\Assert\Assert;

final readonly class IsPartOfMapper
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private string $publicBaseUrl,
    ) {
    }

    public function fromRawUrl(RawUrlDto $rawUrl): ?UrlReference
    {
        if ($rawUrl->mainDocumentReference === null) {
            return null;
        }

        return new UrlReference(
            resource: $this->getResource($rawUrl),
            officieleTitel: $rawUrl->mainDocumentReference->documentFileName,
        );
    }

    private function getResource(RawUrlDto $rawUrl): string
    {
        Assert::notNull($rawUrl->mainDocumentReference);

        $subpath = $this->urlGenerator->generate(
            name: 'app_dossier_file_download',
            parameters: [
                'prefix' => $rawUrl->documentPrefix,
                'dossierId' => $rawUrl->dossierNr,
                'type' => $rawUrl->mainDocumentReference->source->value,
                'id' => $rawUrl->mainDocumentReference->id,
            ],
        );

        return $this->publicBaseUrl . $subpath;
    }
}
