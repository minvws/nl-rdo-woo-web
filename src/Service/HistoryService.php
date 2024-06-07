<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Entity\Document;
use App\Entity\History;
use App\Entity\Inquiry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Uid\Uuid;

class HistoryService
{
    public const TYPE_DOSSIER = 'dossier';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_INQUIRY = 'inquiry';

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
    public function addDossierEntry(
        AbstractDossier $dossier,
        string $key,
        array $context = [],
        string $mode = self::MODE_BOTH,
    ): void {
        $this->addEntry(self::TYPE_DOSSIER, $dossier->getId(), $key, $context, $mode, flush: true);
    }

    /**
     * @param mixed[] $context
     */
    public function addDocumentEntry(Document $document, string $key, array $context, string $mode = self::MODE_BOTH, bool $flush = true): void
    {
        $this->addEntry(self::TYPE_DOCUMENT, $document->getId(), $key, $context, $mode, $flush);
    }

    /**
     * @param mixed[] $context
     */
    public function addInquiryEntry(Inquiry $inquiry, string $key, array $context, string $mode = self::MODE_BOTH): void
    {
        $this->addEntry(self::TYPE_INQUIRY, $inquiry->getId(), $key, $context, $mode, flush: false);
    }

    /**
     * @param mixed[] $context
     */
    protected function addEntry(string $type, Uuid $identifier, string $key, array $context, string $mode, bool $flush): void
    {
        $history = new History();
        $history->setCreatedDt(new \DateTimeImmutable());
        $history->setType($type);
        $history->setIdentifier($identifier);
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
    public function getHistory(string $type, string $identifier, string $mode, ?int $max = null): array
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
