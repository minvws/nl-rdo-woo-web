<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer\Mapper;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use App\Domain\WooIndex\Producer\Repository\RawReferenceDto;
use App\Domain\WooIndex\Producer\Repository\RawUrlDto;
use App\Domain\WooIndex\Producer\UrlReference;
use Doctrine\Common\Collections\ArrayCollection;

final readonly class HasPartsMapper
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private string $publicBaseUrl,
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
                resource: $this->getResource($rawUrl->documentPrefix, $rawUrl->dossierNr, $dto),
                officieleTitel: $dto->documentFileName,
            ))
            ?? null;
    }

    private function getResource(string $documentPrefix, string $dossierNr, RawReferenceDto $dto): string
    {
        $subpath = $this->urlGenerator->generate(
            name: 'app_dossier_file_download',
            parameters: [
                'prefix' => $documentPrefix,
                'dossierId' => $dossierNr,
                'type' => $dto->source->value,
                'id' => $dto->id,
            ],
        );

        return $this->publicBaseUrl . $subpath;
    }
}
