<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType\Mapper;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Search\Index\Dossier\DossierIndexer;
use App\Domain\Search\Index\ElasticDocument;
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

        $dossier = $this->dossierIndexer->map($entity->getDossier())->getDocumentValues();
        $dossierNr = $entity->getDossier()->getDossierNr();
        $file = $entity->getFileInfo();

        $fields = [
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getType(),
            'date' => $entity->getFormalDate()->format(\DateTimeInterface::ATOM),
            'filename' => $file->getName(),
            'grounds' => $entity->getGrounds(),
            'dossier_nr' => [$dossierNr],
            'dossiers' => [$dossier],
        ];

        if ($metadata !== null) {
            $fields['metadata'] = $metadata;
        }

        if ($pages !== null) {
            $fields['pages'] = $pages;
        }

        return new ElasticDocument(
            $this->getId($entity),
            ElasticDocumentType::fromEntity($entity),
            $fields,
        );
    }

    public function getId(object $entity): string
    {
        /** @var AbstractAttachment|AbstractMainDocument $entity */
        Assert::isInstanceOfAny($entity, [AbstractAttachment::class, AbstractMainDocument::class]);

        return $entity->getId()->toRfc4122();
    }
}
