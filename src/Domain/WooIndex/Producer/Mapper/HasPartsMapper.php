<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer\Mapper;

use Doctrine\Common\Collections\ArrayCollection;
use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\WooIndex\Producer\Repository\RawReferenceDto;
use Shared\Domain\WooIndex\Producer\Repository\RawUrlDto;
use Shared\Domain\WooIndex\Producer\UrlReference;
use Shared\ValueObject\Url;

final readonly class HasPartsMapper
{
    public function __construct(
        private PublicUrlGenerator $publicUrlGenerator,
    ) {
    }

    /**
     * @return ?ArrayCollection<array-key,UrlReference>
     */
    public function fromRawUrl(RawUrlDto $rawUrl): ?ArrayCollection
    {
        return $rawUrl
            ->hasParts
            ?->map(fn (RawReferenceDto $dto) => new UrlReference(
                resource: $this->getResource($rawUrl->documentPrefix, $rawUrl->dossierNumber, $dto),
                officieleTitel: $dto->documentFileName,
            ))
            ?? null;
    }

    private function getResource(string $documentPrefix, string $dossierNumber, RawReferenceDto $dto): Url
    {
        return $this->publicUrlGenerator->buildUrlFromRoute(DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD, [
            'documentPrefix' => $documentPrefix,
            'dossierNumber' => $dossierNumber,
            'type' => $dto->source->value,
            'id' => $dto->id,
        ]);
    }
}
