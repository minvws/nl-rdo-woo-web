<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType\Mapper;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Search\Index\Dossier\DossierIndexer;
use App\Domain\Search\Index\Dossier\Mapper\PrefixedDossierNr;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentId;
use App\Domain\Search\Index\ElasticDocumentType;
use Webmozart\Assert\Assert;

readonly class AttachmentAndMainDocumentMapper implements ElasticSubTypeMapperInterface
{
    public function __construct(
        private DossierIndexer $dossierIndexer,
    ) {
    }

    public function supports(object $entity): bool
    {
        return $entity instanceof AbstractAttachment || $entity instanceof AbstractMainDocument;
    }

    /**
     * @param string[]               $metadata
     * @param array<int, mixed>|null $pages
     */
    public function map(object $entity, ?array $metadata = null, ?array $pages = null): ElasticDocument
    {
        /** @var AbstractAttachment|AbstractMainDocument $entity */
        Assert::isInstanceOfAny($entity, [AbstractAttachment::class, AbstractMainDocument::class]);

        $dossierDocument = $this->dossierIndexer->map($entity->getDossier());
        $file = $entity->getFileInfo();

        $fields = [
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getType(),
            'source_type' => $file->getSourceType(),
            'date' => $entity->getFormalDate()->format(\DateTimeInterface::ATOM),
            'filename' => $file->getName(),
            'grounds' => $entity->getGrounds(),
            'dossiers' => [
                $dossierDocument->getDocumentValues(),
            ],
            'prefixed_dossier_nr' => PrefixedDossierNr::forDossier($entity->getDossier()),
        ];

        if ($metadata !== null) {
            $fields['metadata'] = $metadata;
        }

        if ($pages !== null) {
            $fields['pages'] = $pages;
        }

        return new ElasticDocument(
            ElasticDocumentId::forObject($entity),
            ElasticDocumentType::fromEntity($entity->getDossier()),
            ElasticDocumentType::fromEntity($entity),
            $fields,
        );
    }
}
