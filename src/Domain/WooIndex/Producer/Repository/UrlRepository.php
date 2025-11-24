<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 *
 * @phpstan-type SqlRawDocument array{
 *  id: Uuid,
 *  updatedAt: \DateTimeInterface,
 *  documentDate: ?\DateTimeInterface,
 *  documentFileName: string,
 * }
 */
final readonly class UrlRepository
{
    /**
     * @SuppressWarnings("PHPMD.CamelCaseParameterName")
     */
    public function __construct(private EntityManagerInterface $_em)
    {
    }

    /**
     * @return iterable<array-key,RawUrlDto>
     */
    public function getPublishedDocuments(): iterable
    {
        $wooDecisions = $this->_em
            ->createQueryBuilder()
            ->select(sprintf(
                'new %s(
                    d.id,
                    d.documentPrefix,
                    d.dossierNr,
                    d.publicationDate,
                    new %s(
                        :dossier_file_type,
                        md.id,
                        md.fileInfo.name
                    )
                )',
                WooDecisionDto::class,
                RawReferenceDto::class,
            ))
            ->from(WooDecision::class, 'd')
            ->join('d.document', 'md')
            ->where('d.status = :dossier_status')
            ->orderBy('d.updatedAt', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->setParameter('dossier_status', DossierStatus::PUBLISHED->value)
            ->setParameter('dossier_file_type', DossierFileType::MAIN_DOCUMENT->value)
            ->getQuery()
            ->toIterable();

        foreach ($wooDecisions as $wooDecisionDto) {
            yield from $this->doGetPublishedDocuments($wooDecisionDto);
        }
    }

    /**
     * @return iterable<array-key,RawUrlDto>
     */
    private function doGetPublishedDocuments(WooDecisionDto $dto): iterable
    {
        /** @var iterable<array-key,SqlRawDocument> */
        $documents = $this->_em
            ->createQueryBuilder()
            ->select('doc.id')
            ->addSelect('doc.updatedAt')
            ->addSelect('doc.documentDate')
            ->addSelect('doc.fileInfo.name AS documentFileName')
            ->from(Document::class, 'doc')
            ->where(':dossierId MEMBER OF doc.dossiers')
            ->andWhere('doc.judgement in (:judgements)')
            ->andWhere('doc.fileInfo.uploaded = true')
            ->orderBy('doc.updatedAt', 'ASC')
            ->addOrderBy('doc.id', 'ASC')
            ->setParameter('dossierId', $dto->id)
            ->setParameter('judgements', [Judgement::PUBLIC, Judgement::PARTIAL_PUBLIC])
            ->getQuery()
            ->toIterable();

        foreach ($documents as $document) {
            yield new RawUrlDto(
                source: DossierFileType::DOCUMENT,
                id: $document['id'],
                documentUpdatedAt: $document['updatedAt'],
                documentDate: $document['documentDate'] ?? $dto->publicationDate,
                documentFileName: $document['documentFileName'],
                dossierId: $dto->id,
                documentPrefix: $dto->documentPrefix,
                dossierNr: $dto->dossierNr,
                dossierType: DossierType::WOO_DECISION,
                mainDocumentReference: $dto->mainDocumentReference,
            );
        }
    }

    /**
     * @return iterable<array-key,RawUrlDto>
     */
    public function getPublishedMainDocuments(): iterable
    {
        /** @var iterable<array-key,RawUrlDto> $result */
        $result = $this
            ->_em
            ->createQueryBuilder()
            ->select(sprintf(
                'new %s(
                    :dossier_file_type AS source,
                    md.id AS id,
                    md.updatedAt AS documentUpdatedAt,
                    md.formalDate AS documentDate,
                    md.fileInfo.name AS documentFileName,
                    dos.id AS dossierId,
                    dos.documentPrefix AS documentPrefix,
                    dos.dossierNr AS dossierNr,
                    TYPE(dos) AS dossierType
                )',
                RawUrlDto::class,
            ))
            ->from(AbstractMainDocument::class, 'md')
            ->join('md.dossier', 'dos')
            ->where('dos.status = :dossier_status')
            ->andWhere('md.fileInfo.uploaded = true')
            ->orderBy('md.updatedAt', 'ASC')
            ->addOrderBy('md.id', 'ASC')
            ->setParameter('dossier_file_type', DossierFileType::MAIN_DOCUMENT->value)
            ->setParameter('dossier_status', DossierStatus::PUBLISHED->value)
            ->getQuery()
            ->toIterable();

        foreach ($result as $mainDocument) {
            /** @var ArrayCollection<array-key,RawReferenceDto> $hasParts */
            $hasParts = new ArrayCollection([]);

            $this->addAttachmentReferences($mainDocument, $hasParts);
            $this->addDocumentReferences($mainDocument, $hasParts);

            if (! $hasParts->isEmpty()) {
                $mainDocument->hasParts = $hasParts;
            }

            yield $mainDocument;
        }
    }

    /**
     * @param ArrayCollection<array-key,RawReferenceDto> $hasParts
     */
    private function addAttachmentReferences(RawUrlDto $mainDocument, ArrayCollection $hasParts): void
    {
        if (! $mainDocument->dossierType->hasAttachments()) {
            return;
        }

        /** @var class-string<AbstractDossier&EntityWithAttachments> */
        $dossierClass = $mainDocument->dossierType->getDossierClass();

        /** @var iterable<array-key,RawReferenceDto> */
        $result = $this
            ->_em
            ->createQueryBuilder()
            ->select(sprintf(
                'new %s(
                    :dossier_file_type,
                    a.id,
                    a.fileInfo.name
                )',
                RawReferenceDto::class,
            ))
            ->from((new $dossierClass())->getAttachmentEntityClass(), 'a')
            ->where('a.dossier = :dossierId')
            ->andWhere('a.fileInfo.uploaded = true')
            ->orderBy('a.updatedAt', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->setParameter('dossierId', $mainDocument->dossierId)
            ->setParameter('dossier_file_type', DossierFileType::ATTACHMENT->value)
            ->getQuery()
            ->getResult();

        foreach ($result as $attachment) {
            $hasParts->add($attachment);
        }
    }

    /**
     * @param ArrayCollection<array-key,RawReferenceDto> $hasParts
     */
    private function addDocumentReferences(RawUrlDto $mainDocument, ArrayCollection $hasParts): void
    {
        if (! $mainDocument->dossierType->isWooDecision()) {
            return;
        }

        /** @var iterable<array-key,RawReferenceDto> $result */
        $result = $this
            ->_em
            ->createQueryBuilder()
            ->select(sprintf(
                'new %s(
                    :dossier_file_type,
                    doc.id,
                    doc.fileInfo.name
                )',
                RawReferenceDto::class,
            ))
            ->from(Document::class, 'doc')
            ->where(':dossierId MEMBER OF doc.dossiers')
            ->andWhere('doc.judgement in (:judgements)')
            ->andWhere('doc.fileInfo.uploaded = true')
            ->orderBy('doc.updatedAt', 'ASC')
            ->addOrderBy('doc.id', 'ASC')
            ->setParameter('dossier_file_type', DossierFileType::DOCUMENT->value)
            ->setParameter('dossierId', $mainDocument->dossierId)
            ->setParameter('judgements', [Judgement::PUBLIC, Judgement::PARTIAL_PUBLIC])
            ->getQuery()
            ->getResult();

        foreach ($result as $attachment) {
            $hasParts->add($attachment);
        }
    }

    /**
     * @return iterable<array-key,RawUrlDto>
     */
    public function getPublishedAttachments(): iterable
    {
        /** @var iterable<array-key,RawUrlDto> $result */
        $result = $this
            ->_em
            ->createQueryBuilder()
            ->select(sprintf(
                'new %s(
                    :dossier_file_type AS source,
                    a.id AS id,
                    a.updatedAt AS documentUpdatedAt,
                    a.formalDate AS documentDate,
                    a.fileInfo.name AS documentFileName,
                    dos.id AS dossierId,
                    dos.documentPrefix AS documentPrefix,
                    dos.dossierNr AS dossierNr,
                    TYPE(dos) AS dossierType
                )',
                RawUrlDto::class,
            ))
            ->from(AbstractAttachment::class, 'a')
            ->join('a.dossier', 'dos')
            ->where('dos.status = :dossier_status')
            ->andWhere('a.fileInfo.uploaded = true')
            ->orderBy('a.updatedAt', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->setParameter('dossier_file_type', DossierFileType::ATTACHMENT->value)
            ->setParameter('dossier_status', DossierStatus::PUBLISHED->value)
            ->getQuery()
            ->toIterable();

        foreach ($result as $attachment) {
            $this->addMainDocumentReference($attachment);

            yield $attachment;
        }
    }

    private function addMainDocumentReference(RawUrlDto $dto): void
    {
        /** @var ?RawReferenceDto $result */
        $result = $this
            ->_em
            ->createQueryBuilder()
            ->select(sprintf(
                'new %s(
                    :dossier_file_type,
                    md.id,
                    md.fileInfo.name
                )',
                RawReferenceDto::class,
            ))
            ->from($dto->dossierType->getDossierClass(), 'dos')
            ->join('dos.document', 'md')
            ->where('dos.id = :id')
            ->setParameter('id', $dto->dossierId)
            ->setParameter('dossier_file_type', DossierFileType::MAIN_DOCUMENT->value)
            ->getQuery()
            ->getSingleResult();

        if ($result !== null) {
            $dto->mainDocumentReference = $result;
        }
    }
}
