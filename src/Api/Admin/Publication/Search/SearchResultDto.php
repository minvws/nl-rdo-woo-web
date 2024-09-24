<?php

declare(strict_types=1);

namespace App\Api\Admin\Publication\Search;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Entity\Document;
use App\Entity\Dossier;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[GetCollection(
    uriTemplate: 'publication/search',
    parameters: [
        'q' => new QueryParameter(
            required: true,
            constraints: [
                new Assert\NotBlank(normalizer: 'trim'),
                new Assert\Length(min: 3, max: 255, normalizer: 'trim'),
            ],
        ),
    ],
    paginationEnabled: false,
    security: "is_granted('AuthMatrix.dossier.read')",
    stateless: false,
    openapi: new Operation(
        tags: ['PublicationSearch']
    ),
    provider: SearchProvider::class,
)]
final readonly class SearchResultDto
{
    public function __construct(
        public string $id,
        public SearchResultType $type,
        public string $title,
        public string $link,
    ) {
    }

    public static function fromEntity(AbstractDossier|Document $entity, UrlGeneratorInterface $urlGenerator): static
    {
        return match (true) {
            $entity instanceof AbstractDossier => self::fromAbstractDossierEntity($entity, $urlGenerator),
            $entity instanceof Document => self::fromDocumentEntity($entity, $urlGenerator),
        };
    }

    private static function fromAbstractDossierEntity(AbstractDossier $entity, UrlGeneratorInterface $urlGenerator): static
    {
        return new static(
            id: $entity->getDossierNr(),
            type: SearchResultType::DOSSIER,
            title: $entity->getTitle() ?? '',
            link: $urlGenerator->generate(
                'app_admin_dossier',
                ['prefix' => $entity->getDocumentPrefix(), 'dossierId' => $entity->getDossierNr()],
            ),
        );
    }

    private static function fromDocumentEntity(Document $entity, UrlGeneratorInterface $urlGenerator): static
    {
        /** @var Dossier $firstDossier */
        $firstDossier = $entity->getDossiers()->first();

        return new static(
            id: $entity->getDocumentNr(),
            type: SearchResultType::DOCUMENT,
            title: $entity->getFileInfo()->getName() ?? '',
            link: $urlGenerator->generate(
                'app_admin_dossier_woodecision_document',
                [
                    'prefix' => $firstDossier->getDocumentPrefix(),
                    'dossierId' => $firstDossier->getDossierNr(),
                    'documentId' => $entity->getDocumentNr(),
                ],
            )
        );
    }
}
