<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Inquiry;
use App\Entity\Inventory;
use App\Service\Storage\DocumentStorageService;
use App\SourceType;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

/**
 * This class will parse an inventory spreadsheet and generates document entities from the given data. Note that this class does not
 * handle the content of the documents itself, just the metadata.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class InventoryService
{
    protected EntityManagerInterface $doctrine;
    protected DocumentStorageService $documentStorage;
    protected LoggerInterface $logger;

    public const FIELD_DATE = 'date';
    public const FIELD_DOCUMENT = 'document';
    public const FIELD_FAMILY = 'family';
    public const FIELD_FILETYPE = 'filetype';
    public const FIELD_GROUND = 'ground';
    public const FIELD_ID = 'id';
    public const FIELD_JUDGEMENT = 'judgement';
    public const FIELD_PERIOD = 'period';
    public const FIELD_SUBJECT = 'subject';
    public const FIELD_THREADID = 'threadId';
    public const FIELD_CASENR = 'casenr';
    public const FIELD_SUSPENDED = 'suspended';

    // These headers may or may not be present in the spreadsheet
    /** @var array|string[] */
    protected array $optionalHeaders = [
        self::FIELD_CASENR,
        self::FIELD_SUSPENDED,
    ];

    // These are the possible names for the given headers. Any leading digits will be stripped from the header name.
    /** @var array<string, string[]> */
    protected array $headers = [
        self::FIELD_DATE => ['date', 'datum'],
        self::FIELD_DOCUMENT => ['document', 'document id', 'documentnr', 'document nr', 'documentnr.', 'document nr.'],
        self::FIELD_FAMILY => ['family', 'familie', 'family id'],
        self::FIELD_FILETYPE => ['file type', 'filetype'],
        self::FIELD_GROUND => ['beoordelingsgrond', 'grond'],
        self::FIELD_ID => ['id'],
        self::FIELD_JUDGEMENT => ['beoordeling'],
        self::FIELD_PERIOD => ['periode', 'period'],
        self::FIELD_SUBJECT => ['onderwerp', 'subject'],
        self::FIELD_THREADID => ['thread id', 'email thread id', 'email thread'],
        self::FIELD_CASENR => ['zaaknr', 'casenr', 'zaak', 'case'],
        self::FIELD_SUSPENDED => ['opgeschort', 'suspended'],
    ];

    /** @var array<int, string[]> */
    protected array $errors = [];

    public function __construct(EntityManagerInterface $doctrine, DocumentStorageService $documentStorage, LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->documentStorage = $documentStorage;
        $this->logger = $logger;
    }

    /**
     * Store the inventory spreadsheet on disk, process the sheet and attach found documents to the dossier.
     *
     * @return array<int, string[]> errors
     */
    public function processInventory(UploadedFile $excel, Dossier $dossier): array
    {
        $inventory = $this->storeInventoryFile($excel, $dossier);
        if (! $inventory) {
            $this->logger->error('Could not store the inventory spreadsheet.', [
                'dossier' => $dossier->getId(),
                'filename' => $excel->getClientOriginalName(),
            ]);

            $this->addError(0, 'Could not store the inventory spreadsheet.');

            return $this->getErrors();
        }

        // Process the spreadsheet and time it
        $start = microtime(true);
        $result = $this->processSheet($inventory, $dossier);

        $this->logger->info('Processed inventory spreadsheet.', [
            'dossier' => $dossier->getId(),
            'filename' => $excel->getClientOriginalName(),
            'duration' => microtime(true) - $start,
            'success' => $result,
            'errors' => $this->getErrors(),
        ]);

        return $this->getErrors();
    }

    /**
     * Process spreadsheet and attach found documents to the dossier. Will return an
     * array of issues in case of failure.
     */
    protected function processSheet(Inventory $inventory, Dossier $dossier): bool
    {
        $tmpFilename = $this->documentStorage->downloadDocument($inventory);
        if (! $tmpFilename) {
            $this->addError(0, 'Could not store the inventory spreadsheet.');

            return false;
        }

        // Load the spreadsheet from the local temporary file
        $spreadsheet = IOFactory::load($tmpFilename);

        try {
            // Assume only first worksheet
            $sheet = $spreadsheet->getSheet(0);
            $headers = $this->validateHeaders($sheet);
        } catch (\Exception $e) {
            $this->documentStorage->removeDownload($tmpFilename);

            $this->logger->error('Error while validating the headers in the spreadsheet.', [
                'dossier' => $dossier->getId(),
                'filename' => $inventory->getFilename(),
                'exception' => $e->getMessage(),
            ]);

            // Could not find the correct headers
            $this->addError(0, 'Error while validating the headers in the spreadsheet.');

            return false;
        }

        if (count($headers['missing']) > 0) {
            $this->documentStorage->removeDownload($tmpFilename);

            $this->logger->error('Could not find the correct headers in the spreadsheet.', [
                'dossier' => $dossier->getId(),
                'filename' => $inventory->getFilename(),
                'missing' => $headers['missing'],
                'found' => $headers['found'],
            ]);

            // Could not find the correct headers
            $this->addError(0, 'Could not find the correct headers in the spreadsheet. Missing: ' . implode(', ', $headers['missing']));

            return false;
        }

        $this->processRows($sheet, $dossier, $headers, $inventory);

        // Remove the temporary file
        $this->documentStorage->removeDownload($tmpFilename);

        if (count($this->getErrors()) > 0) {
            // Errors encountered, don't persist dossier
            return false;
        }

        // Persist dossier with new documents
        $this->doctrine->persist($dossier);
        $this->doctrine->flush();

        return true;
    }

    /**
     * @param array{found: string[], missing: string[]} $headers
     *
     * @return string[] errors
     */
    protected function processRows(Worksheet $sheet, Dossier $dossier, array $headers, Inventory $inventory): array
    {
        $errors = [];

        // Store current documents, so we can see which are new and which can be removed
        $tobeRemovedDocs = [];
        foreach ($dossier->getDocuments() as $entry) {
            if ($entry instanceof Inventory) {
                continue;
            }

            $tobeRemovedDocs[$entry->getDocumentNr()] = $entry;
        }

        // Process each row
        foreach ($sheet->getRowIterator(2) as $row) {
            // Create document or attach an existing document to the dossier
            try {
                $document = $this->processRow($sheet, $headers['found'], $row->getRowIndex(), $dossier);
                if (! $document) {
                    // Seems like an empty line
                    continue;
                }

                // This document is added (again), so remove it from the tobeRemovedDocs array
                if (isset($tobeRemovedDocs[$document->getDocumentNr()])) {
                    unset($tobeRemovedDocs[$document->getDocumentNr()]);
                }
            } catch (\Exception $e) {
                $this->logger->error('Error while processing row ' . $row->getRowIndex() . ' in the spreadsheet.', [
                    'dossier' => $dossier->getId(),
                    'filename' => $inventory->getFilename(),
                    'row' => $row->getRowIndex(),
                    'exception' => $e->getMessage(),
                ]);

                // Exception occurred, but we still continue with the next row. Just log the error
                $errors[] = 'Error while processing row ' . $row->getRowIndex() . ' in the spreadsheet: ' . $e->getMessage();
            }
        }

        // We now have a list of old documents that are linked to the dossier, but are not in the new inventory
        // Remove these documents from the dossier
        foreach ($tobeRemovedDocs as $document) {
            $dossier->removeDocument($document);
        }

        return $errors;
    }

    /**
     * Validate a list of headers in the spreadsheet. Will return an array of mapped headers and missing headers, so
     * we can safely use headers['found']['id'] to fetch the column number of the ID column.
     *
     * @return array{found: string[], missing: string[]}
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function validateHeaders(Worksheet $sheet): array
    {
        $foundHeaders = [];
        $missingHeaders = $this->headers;

        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $val = strval($cell->getValue());
                $val = trim(strtolower($val));
                while (ctype_digit($val[0])) {
                    $val = substr($val, 1);
                }
                if (empty($val)) {
                    continue;
                }

                foreach ($missingHeaders as $k => $names) {
                    // Remove digit prefixes from the beginning of the string if any

                    foreach ($names as $name) {
                        // Check if it matches the header with some fuzziness
                        if (levenshtein(strtolower($name), $val) < 2) {
                            $foundHeaders[$k] = $cell->getColumn();
                            unset($missingHeaders[$k]);
                        }
                    }
                }
            }
        }

        // Optional headers are never missing headers
        foreach ($this->optionalHeaders as $header) {
            unset($missingHeaders[$header]);
        }

        return [
            'found' => $foundHeaders,
            'missing' => array_keys($missingHeaders),
        ];
    }

    /**
     * Store the inventory to disk and add the inventory document to the dossier.
     */
    protected function storeInventoryFile(UploadedFile $file, Dossier $dossier): ?Inventory
    {
        $inventory = $this->doctrine->getRepository(Inventory::class)->findOneBy([
            'documentNr' => $dossier->getDossierNr(),
        ]);

        // If the document is not contained in any of the dossiers, simply add it to the list.
        if ($inventory && ! $inventory->getDossiers()->contains($dossier)) {
            $dossier->addDocument($inventory);
        }

        // if no document is found, create a new document
        if (! $inventory) {
            // Create inventory document if not exists
            $inventory = new Inventory();
            $inventory->setCreatedAt(new \DateTimeImmutable());
            $dossier->addDocument($inventory);
        }

        // From here, we can update the current (new) document
        $inventory->setDocumentDate(new \DateTimeImmutable());
        $inventory->setDocumentNr($dossier->getDossierNr());
        $inventory->setUpdatedAt(new \DateTimeImmutable());
        // @TODO: these fields should not be inside an inventory
        $inventory->setPageCount(0);
        $inventory->setDuration(0);
        $inventory->setDocumentId(0);
        $inventory->setFamilyId(0);
        $inventory->setThreadId(0);
        $inventory->setGrounds([]);
        $inventory->setJudgement('');
        $inventory->setPeriod('');
        $inventory->setSubjects([]);
        $inventory->setSuspended(false);
        $inventory->setWithdrawn(false);

        $inventory->setSourceType(SourceType::SOURCE_SPREADSHEET);
        $inventory->setFileType('pdf');

        // Set original filename
        $filename = 'inventory-' . $dossier->getDossierNr() . '.' . $file->getClientOriginalExtension();
        $inventory->setFilename($filename);

        $this->doctrine->persist($inventory);
        $this->doctrine->persist($dossier);

        if (! $this->documentStorage->storeDocument($file, $inventory)) {
            return null;
        }

        // All is well, flush the changes
        $this->doctrine->flush();

        return $inventory;
    }

    /**
     * If the document has case numbers, add it to those inquiries. If those inquiries do not exist
     * yet, create them as well.
     *
     * @param string $caseNrs semicolon separated list of case numbers
     */
    protected function addDocumentToCases(string $caseNrs, Document $document): void
    {
        if (empty($caseNrs)) {
            return;
        }

        $caseNrs = explode(';', $caseNrs);
        foreach ($caseNrs as $caseNr) {
            $caseNr = trim($caseNr);

            $inquiry = $this->doctrine->getRepository(Inquiry::class)->findOneBy(['casenr' => $caseNr]);
            if (! $inquiry) {
                // Create inquiry if not exists
                $inquiry = new Inquiry();
                $inquiry->setCasenr($caseNr);
                $inquiry->setToken(Uuid::v6()->toBase58());
                $inquiry->setCreatedAt(new \DateTimeImmutable());
            }

            $inquiry->setUpdatedAt(new \DateTimeImmutable());

            // Add this document, and the dossiers it belongs to, to the inquiry
            $inquiry->addDocument($document);
            foreach ($document->getDossiers() as $dossier) {
                $inquiry->addDossier($dossier);
            }

            $this->doctrine->persist($inquiry);
            $this->doctrine->flush();
        }
    }

    /**
     * Process a single row of the spreadsheet. Creates a document if one didn't exist already, or adds an already
     * existing document to the dossier. Also generates or updates inquiries/cases.
     *
     * NOTE: this method does not flush the changes to the database.
     *
     * @param string[] $headers
     *
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function processRow(Worksheet $sheet, array $headers, int $rowIdx, Dossier $dossier): ?Document
    {
        $documentId = intval($sheet->getCell($headers['id'] . $rowIdx)->getValue());
        if (empty($documentId)) {
            return null;
        }

        $documentDate = new \DateTimeImmutable(strval($sheet->getCell($headers[self::FIELD_DATE] . $rowIdx)->getValue()));
        $fileName = strval($sheet->getCell($headers[self::FIELD_DOCUMENT] . $rowIdx)->getValue());
        $familyId = intval($sheet->getCell($headers[self::FIELD_FAMILY] . $rowIdx)->getValue());
        $threadId = intval($sheet->getCell($headers[self::FIELD_THREADID] . $rowIdx)->getValue());
        $judgement = strval($sheet->getCell($headers[self::FIELD_JUDGEMENT] . $rowIdx)->getValue());
        $grounds = explode(';', strval($sheet->getCell($headers[self::FIELD_GROUND] . $rowIdx)->getValue()));
        $subjects = explode(';', strval($sheet->getCell($headers[self::FIELD_SUBJECT] . $rowIdx)->getValue()));
        $period = strval($sheet->getCell($headers[self::FIELD_PERIOD] . $rowIdx)->getValue());

        $documentNr = $dossier->getDocumentPrefix() . '-' . $documentId;
        $sourceType = SourceType::getType(strval($sheet->getCell($headers[self::FIELD_FILETYPE] . $rowIdx)->getValue()));

        if (isset($headers[self::FIELD_CASENR])) {
            $caseNrs = strval($sheet->getCell($headers[self::FIELD_CASENR] . $rowIdx)->getValue());
        } else {
            $caseNrs = '';
        }

        if (isset($headers[self::FIELD_SUSPENDED])) {
            $suspended = strval($sheet->getCell($headers[self::FIELD_SUSPENDED] . $rowIdx)->getValue());
        } else {
            $suspended = false;
        }

        if (empty($fileName)) {
            // @TODO: FILETYPE DOES NOT HAVE TO BE PDF
            $fileName = $documentNr . '.pdf';
        }

        // Trim and remove empty elements
        $grounds = array_map('trim', $grounds);
        $grounds = array_filter($grounds);
        $subjects = array_map('trim', $subjects);
        $subjects = array_filter($subjects);

        // Check if document already exists in the dossier
        $document = $this->doctrine->getRepository(Document::class)->findOneBy([
            'documentNr' => $documentNr,
        ]);

        if ($document && $document->getDossiers()->contains($dossier) === false) {
            // Document exists, but it is not part of the current dossier. We cannot add it
            $this->addError($rowIdx, sprintf('Document %s already exists in another dossier', $document->getDocumentId()));

            return null;
        }

        // If document didn't exist yet, create it
        if (! $document) {
            $document = new Document();
            $document->setCreatedAt(new \DateTimeImmutable());
        }

        // Update all fields from the existing (or new) document.
        $document->setUpdatedAt(new \DateTimeImmutable());
        $document->setDocumentDate($documentDate);
        $document->setFilename($fileName);
        $document->setFamilyId($familyId);
        $document->setDocumentId($documentId);
        $document->setThreadId($threadId);
        $document->setJudgement($judgement);
        $document->setGrounds($grounds);
        $document->setSubjects($subjects);
        $document->setPeriod($period);
        $document->setSourceType($sourceType);
        $document->setDocumentNr($documentNr);

        $document->setWithdrawn(false);
        $document->setSuspended($this->isTrue($suspended));

        // We don't know what type of file we will upload. So we set all fields to empty
        $document->setFileType('');
        $document->setMimetype('');
        $document->setFilepath('');
        $document->setFilesize(0);
        $document->setUploaded(false);

        $this->doctrine->persist($document);

        // Add document to the dossier
        $dossier->addDocument($document);

        // We need to persist the dossier, because we added a document to it. This would also make the dossier
        // visible in the document, so the addDocumentToCases() method can find the dossier.
        $this->doctrine->persist($dossier);

        // Add document to woo request case nr (if any), or create new woo request if not already present
        $this->addDocumentToCases($caseNrs, $document);

        return $document;
    }

    /**
     * Returns true when the given value resembles a value that can be considered to be true.
     */
    protected function isTrue(string|bool $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower($value), ['true', 'ja', 'yes', '1', 'y', 'j']);
    }

    protected function addError(int $rowIdx, string $message): void
    {
        if (! isset($this->errors[$rowIdx])) {
            $this->errors[$rowIdx] = [];
        }

        $this->errors[$rowIdx][] = $message;
    }

    /**
     * @return array<int, string[]>
     */
    protected function getErrors(): array
    {
        return $this->errors;
    }
}
