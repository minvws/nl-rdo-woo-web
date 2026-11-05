<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer\Mapper;

use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\WooIndex\Producer\Repository\RawUrlDto;
use Shared\Domain\WooIndex\Producer\UrlReference;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

final readonly class IsPartOfMapper
{
    public function __construct(
        private PublicUrlGenerator $publicUrlGenerator,
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

    private function getResource(RawUrlDto $rawUrl): Url
    {
        Assert::notNull($rawUrl->mainDocumentReference);

        return $this->publicUrlGenerator->buildUrlFromRoute(DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD, [
            'documentPrefix' => $rawUrl->documentPrefix,
            'dossierNumber' => $rawUrl->dossierNumber,
            'type' => $rawUrl->mainDocumentReference->source->value,
            'id' => $rawUrl->mainDocumentReference->id,
        ]);
    }
}
