<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\History;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\Translator;

class HistoryService
{
    public const TYPE_DOSSIER = 'dossier';
    public const TYPE_DOCUMENT = 'document';

    public const MODE_PUBLIC = 'public';
    public const MODE_PRIVATE = 'private';
    public const MODE_BOTH = 'both';

    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly Translator $translator,
    ) {
    }

    /**
     * @param mixed[] $context
     */
    public function addDossierEntry(Dossier $dossier, string $key, array $context, string $mode = self::MODE_BOTH): void
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
        $history->setSite($mode);

        $this->doctrine->persist($history);
        $this->doctrine->flush();
    }

    /**
     * @param mixed[] $context
     */
    public function addDocumentEntry(Document $document, string $key, array $context, string $mode = self::MODE_BOTH, bool $flush = true): void
    {
        $history = new History();
        $history->setType(self::TYPE_DOCUMENT);
        $history->setIdentifier($document->getId());
        $history->setCreatedDt(new \DateTimeImmutable());
        $history->setContextKey($key);
        $history->setContext($context);
        $history->setSite($mode);

        $this->doctrine->persist($history);
        if ($flush) {
            $this->doctrine->flush();
        }
    }

    /**
     * @return array|History[]
     */
    public function getHistory(string $type, string $identifier, string $mode, int $max = null): array
    {
        return $this->doctrine->getRepository(History::class)->getHistory(
            $type,
            $identifier,
            $mode,
            $max
        );
    }

    public function translate(History $entry, string $mode): string
    {
        $key = 'history.' . $entry->getContextKey() . '.' . $mode;

        $context = [];
        foreach ($entry->getContext() as $ctxKey => $values) {
            if (! is_array($values)) {
                $values = [$values];
            }

            foreach ($values as $k => $value) {
                // A value surrounded by % means it needs to be translated first
                if (is_string($value) && str_starts_with($value, '%') and str_ends_with($value, '%')) {
                    $values[$k] = $this->translator->trans(substr($value, 1, -1));
                }
            }

            $context['{' . $ctxKey . '}'] = join(',', $values);
        }

        if ($this->translator->getCatalogue()->has($key)) {
            return $this->translator->trans($key, $context);
        }

        return '';
    }
}
