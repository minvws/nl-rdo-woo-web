<?php

declare(strict_types=1);

namespace Shared\Service;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\History\History;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Uid\Uuid;

use function is_array;
use function is_string;
use function join;
use function str_ends_with;
use function str_starts_with;
use function substr;

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
        Uuid $dossierId,
        string $key,
        array $context = [],
        string $mode = self::MODE_BOTH,
    ): void {
        $this->addEntry(self::TYPE_DOSSIER, $dossierId, $key, $context, $mode, flush: true);
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
        $history->setCreatedDt(new DateTimeImmutable());
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
