<?php

declare(strict_types=1);

namespace Shared\Tests\Story\Demo;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\EntityStorageService;
use SplFileInfo;

use function file_put_contents;
use function implode;
use function is_dir;
use function mkdir;
use function random_int;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function Zenstruck\Foundry\faker;

/**
 * Writes generated files to the storage of the entities that are created by the demo data.
 */
final class DemoFileWriter
{
    private const int MAX_PAGES = 15;

    /** @var list<EntityWithFileInfo> */
    private array $pdfs = [];

    public function __construct(
        private readonly EntityStorageService $storage,
        private readonly PdfBuilder $pdfBuilder = new PdfBuilder(),
    ) {
    }

    /**
     * Stores a unique PDF with a random (or given) number of pages for the entity.
     */
    public function writePdf(EntityWithFileInfo $entity, string $title, ?int $pages = null): void
    {
        $pages ??= $this->randomPageCount();

        $paragraphs = [];
        $paragraphsNeeded = $pages * 5;
        for ($i = 0; $i < $paragraphsNeeded; $i++) {
            $paragraphs[] = faker()->paragraph(random_int(4, 9));
        }

        $this->store(
            $entity,
            $this->pdfBuilder->build($title, $paragraphs, $pages),
            $pages,
        );
    }

    /**
     * Stores a spreadsheet that lists the given documents, like a real production report or inquiry inventory.
     *
     * @param iterable<Document> $documents
     */
    public function writeSpreadsheet(EntityWithFileInfo $entity, iterable $documents, ?string $filename = null): void
    {
        if ($filename !== null) {
            $entity->getFileInfo()->setName($filename)->setType('xlsx');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Document ID', 'Documentnaam', 'Beoordeling', 'Datum', 'Familie ID', 'Draad ID', 'Grondslagen']);

        $row = 2;
        foreach ($documents as $document) {
            $sheet->fromArray([
                (string) $document->getDocumentId(),
                $document->getFileInfo()->getName(),
                $document->getJudgement()?->value,
                $document->getDocumentDate()?->format('Y-m-d'),
                $document->getFamilyId(),
                $document->getThreadId(),
                implode(', ', $document->getGrounds()),
            ], null, sprintf('A%d', $row++));
        }

        $directory = $this->createTempDirectory();
        $path = sprintf('%s/%s', $directory, $entity->getFileInfo()->getName());
        new Xlsx($spreadsheet)->save($path);
        $spreadsheet->disconnectWorksheets();

        $this->storeFile($entity, $path, null);
        $this->removeTempDirectory($directory, $path);
    }

    private function store(EntityWithFileInfo $entity, string $contents, ?int $pages): void
    {
        $directory = $this->createTempDirectory();
        $path = sprintf('%s/%s', $directory, $entity->getFileInfo()->getName());
        file_put_contents($path, $contents);

        $this->storeFile($entity, $path, $pages);
        $this->removeTempDirectory($directory, $path);
    }

    /**
     * Does not flush: the story flushes once per dossier.
     */
    private function storeFile(EntityWithFileInfo $entity, string $path, ?int $pages): void
    {
        if (! $this->storage->storeEntity(new SplFileInfo($path), $entity, false)) {
            throw new RuntimeException(sprintf('Could not store file for %s', $entity->getFileInfo()->getName()));
        }

        if ($pages !== null) {
            $entity->getFileInfo()->setPageCount($pages)->setPaginatable(true);
            $this->pdfs[] = $entity;
        }
    }

    /**
     * Returns the entities with a stored PDF, which still need to be ingested (thumbnails, text extraction).
     *
     * @return list<EntityWithFileInfo>
     */
    public function drainStoredPdfs(): array
    {
        $pdfs = $this->pdfs;
        $this->pdfs = [];

        return $pdfs;
    }

    private function randomPageCount(): int
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 35 => 1,
            $roll <= 75 => random_int(2, 5),
            $roll <= 95 => random_int(6, 10),
            default => random_int(11, self::MAX_PAGES),
        };
    }

    private function createTempDirectory(): string
    {
        $directory = sprintf('%s/%s', sys_get_temp_dir(), uniqid('demo-file-', true));
        if (! is_dir($directory)) {
            mkdir($directory, 0o700, true);
        }

        return $directory;
    }

    private function removeTempDirectory(string $directory, string $path): void
    {
        unlink($path);
        rmdir($directory);
    }
}
