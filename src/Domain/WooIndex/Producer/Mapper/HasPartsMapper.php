<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer\Mapper;

use Doctrine\Common\Collections\ArrayCollection;
use Shared\Domain\WooIndex\Producer\Repository\RawReferenceDto;
use Shared\Domain\WooIndex\Producer\Repository\RawUrlDto;
use Shared\Domain\WooIndex\Producer\UrlReference;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
                resource: $this->getResource($rawUrl->documentPrefix, $rawUrl->dossierNumber, $dto),
                officieleTitel: $dto->documentFileName,
            ))
            ?? null;
    }

    private function getResource(string $documentPrefix, string $dossierNumber, RawReferenceDto $dto): string
    {
        $subpath = $this->urlGenerator->generate(
            name: 'app_dossier_file_download',
            parameters: [
                'documentPrefix' => $documentPrefix,
                'dossierNumber' => $dossierNumber,
                'type' => $dto->source->value,
                'id' => $dto->id,
            ],
        );

        return $this->publicBaseUrl . $subpath;
    }
}
