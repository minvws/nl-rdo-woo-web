<?php

declare(strict_types=1);

namespace Shared\Command;

use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function explode;
use function implode;
use function sprintf;
use function trim;

#[AsCommand(name: self::COMMAND_NAME)]
class NormalizeDocumentGrounds extends Command
{
    public const string COMMAND_NAME = 'woopie:normalize-document-grounds';

    private const int BATCH_SIZE = 1000;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IngestDispatcher $ingestDispatcher,
    ) {
        parent::__construct();
    }

    public function __invoke(
        OutputInterface $output,
        #[Argument(name: 'mapping', description: 'The value mapping excel sheet')]
        string $mappingFilename,
        #[Option(description: 'Dry run', shortcut: 'd')]
        bool $dryRun = false,
        #[Option(
            name: 'no-dispatch-reindex',
            description: 'Do not dispatch Elasticsearch reindex messages for changed documents',
        )]
        bool $noDispatchReindex = false,
    ): int {
        $mapping = $this->loadMapping($mappingFilename);
        $reindex = ! $noDispatchReindex && ! $dryRun;

        $documentQuery = $this->entityManager->getRepository(Document::class)->createQueryBuilder('d')->select('d')->getQuery();
        $checked = 0;
        $changed = [];

        foreach ($documentQuery->toIterable() as $document) {
            Assert::isInstanceOf($document, Document::class);
            $currentGrounds = $document->getGrounds();
            $normalizedGrounds = $this->normalize($currentGrounds, $mapping);
            if ($normalizedGrounds !== $currentGrounds) {
                $document->setGrounds($normalizedGrounds);
                $changed[] = $document;

                $output->writeln(sprintf(
                    '<info>%s: updated [%s] to [%s]</info>',
                    $document->getDocumentNumber()->toString(),
                    implode(';', $currentGrounds),
                    implode(';', $normalizedGrounds),
                ));
            }

            if (++$checked % self::BATCH_SIZE === 0) {
                $this->flush($changed, $dryRun, $reindex);
                $changed = [];
            }
        }

        $this->flush($changed, $dryRun, $reindex);

        if ($dryRun) {
            $output->writeln('<comment>DRY RUN: nothing was updated in the database</comment>');
        }

        $output->writeln(sprintf('<info>FINISHED, checked %d documents</info>', $checked));

        return self::SUCCESS;
    }

    /**
     * @param list<string> $grounds
     * @param array<string, list<string>> $mapping
     *
     * @return list<string>
     */
    private function normalize(array $grounds, array $mapping): array
    {
        $result = [];
        foreach ($grounds as $ground) {
            $ground = trim($ground);
            $result[] = $mapping[$ground] ?? [$ground];
        }

        return array_values(array_unique(array_merge(...$result)));
    }

    /**
     * @param list<Document> $changed
     */
    private function flush(array $changed, bool $dryRun, bool $reindex): void
    {
        if (! $dryRun) {
            $this->entityManager->flush();

            if ($reindex) {
                foreach ($changed as $document) {
                    $this->ingestDispatcher->dispatchIngestMetadataOnlyCommandForEntity($document, forceRefresh: false);
                }
            }
        }

        $this->entityManager->clear();
    }

    /**
     * @return array<string, list<string>>
     */
    private function loadMapping(string $filename): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filename);
        $rows = $spreadsheet->getActiveSheet()->toArray();
        unset($rows[0]); // Skip header

        $mapping = [];
        foreach ($rows as [$from, $to]) {
            if ($from === null) {
                continue;
            }

            Assert::string($from);
            if ($to !== null) {
                Assert::scalar($to);
            }

            $mapping[trim($from)] = $to === null
                ? []
                : array_map(trim(...), explode(';', (string) $to));
        }

        return $mapping;
    }
}
