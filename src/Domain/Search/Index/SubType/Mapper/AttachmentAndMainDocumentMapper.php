<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType\Mapper;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Search\Index\Dossier\DossierIndexer;
use App\Domain\Search\Index\Dossier\Mapper\PrefixedDossierNr;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentId;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Index\Schema\ElasticNestedField;
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
            ElasticField::MIME_TYPE->value => $file->getMimeType(),
            ElasticField::FILE_SIZE->value => $file->getSize(),
            ElasticField::FILE_TYPE->value => $file->getType(),
            ElasticField::SOURCE_TYPE->value => $file->getSourceType(),
            ElasticField::DATE->value => $entity->getFormalDate()->format(\DateTimeInterface::ATOM),
            ElasticField::FILENAME->value => $file->getName(),
            ElasticField::GROUNDS->value => $entity->getGrounds(),
            ElasticNestedField::DOSSIERS->value => [
                $dossierDocument->getDocumentValues(),
            ],
            ElasticField::PREFIXED_DOSSIER_NR->value => PrefixedDossierNr::forDossier($entity->getDossier()),
        ];

        if ($metadata !== null) {
            $fields[ElasticField::METADATA->value] = $metadata;
        }

        if ($pages !== null) {
            $fields[ElasticNestedField::PAGES->value] = $pages;
        }

        return new ElasticDocument(
            ElasticDocumentId::forObject($entity),
            ElasticDocumentType::fromEntity($entity->getDossier()),
            ElasticDocumentType::fromEntity($entity),
            $fields,
        );
    }
}
