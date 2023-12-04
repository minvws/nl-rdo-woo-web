<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\History;
use Doctrine\ORM\EntityManagerInterface;

class HistoryService
{
    protected const TYPE_DOSSIER = 'dossier';
    protected const TYPE_DOCUMENT = 'document';

    public function __construct(
        private readonly EntityManagerInterface $doctrine,
    ) {
    }

    /**
     * @param mixed[] $context
     */
    public function addDossierEntry(Dossier $dossier, string $key, array $context): void
    {
        if ($dossier->getId() === null) {
            return;
        }

        $history = new History();
        $history->setCreatedDt(new \DateTimeImmutable());
        $history->setType(self::TYPE_DOSSIER);
        $history->setIdentifier($dossier->getId());
        $history->setContextKey($key);
        $history->setContext($context);

        $this->doctrine->persist($history);
        $this->doctrine->flush();
    }

    /**
     * @param mixed[] $context
     */
    public function addDocumentEntry(Document $document, string $key, array $context): void
    {
        $history = new History();
        $history->setCreatedDt(new \DateTimeImmutable());
        $history->setType(self::TYPE_DOCUMENT);
        $history->setIdentifier($document->getId());
        $history->setContextKey($key);
        $history->setContext($context);

        $this->doctrine->persist($history);
        $this->doctrine->flush();
    }

    /**
     * @return array|History[]
     */
    public function getHistory(string $type, string $identifier, int $max = null): array
    {
        return $this->doctrine->getRepository(History::class)->findBy([
            'type' => $type,
            'identifier' => $identifier,
        ], ['createdDt' => 'DESC'], $max);
    }
}
