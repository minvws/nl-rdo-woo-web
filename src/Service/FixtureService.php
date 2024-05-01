<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Department;
use App\Entity\Document;
use App\Entity\DocumentPrefix;
use App\Entity\Dossier;
use App\Entity\InventoryProcessRun;
use App\Entity\Judgement;
use App\Entity\Organisation;
use App\Exception\FixtureInventoryException;
use App\Message\ProcessDocumentMessage;
use App\Service\Elastic\ElasticService;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use App\Service\Inventory\InventoryRunProcessor;
use App\Service\Storage\DocumentStorageService;
use App\SourceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Creates Dossier/Document fixtures based on the given data.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FixtureService
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly IngestService $ingester,
        private readonly InventoryRunProcessor $runProcessor,
        private readonly MessageBusInterface $messageBus,
        private readonly ElasticService $elasticService,
        private readonly DocumentStorageService $documentStorage,
    ) {
    }

    /**
     * @param string[] $dossier
     *
     * @throws \Exception
     */
    public function create(array $dossier, string $path): void
    {
        $inventoryFile = $path . '/' . $dossier['inventory_path'];
        if (! file_exists($inventoryFile)) {
            throw new \Exception("File $inventoryFile does not exist");
        }

        if (isset($dossier['document_path'])) {
            $documentsFile = $path . '/' . $dossier['document_path'];
            if (! file_exists($documentsFile)) {
                throw new \Exception("File $documentsFile does not exist");
            }
        } else {
            $documentsFile = null;
        }

        $dossierEntity = $this->createDossier($dossier, $inventoryFile, $documentsFile);
        $this->elasticService->updateDossier($dossierEntity, false);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws \Exception
     */
    protected function createDossier(array $data, string $inventoryPath, ?string $documentsPath): Dossier
    {
        $orgs = $this->doctrine->getRepository(Organisation::class)->findAll();
        shuffle($orgs);
        $organisation = reset($orgs);

        if (! $organisation instanceof Organisation) {
            throw new \RuntimeException('No organisations found');
        }

        $this->ensurePrefixExists($data['document_prefix']);

        $dossier = new WooDecision();
        $dossier->setDateFrom(new \DateTimeImmutable($data['period_from']));
        $dossier->setDateTo(new \DateTimeImmutable($data['period_to']));
        $dossier->setDecision($data['decision']);
        $dossier->setDocumentPrefix($data['document_prefix']);
        $dossier->setDossierNr($data['id']);
        $dossier->setStatus(DossierStatus::tryFrom($data['status']) ?? DossierStatus::PUBLISHED);
        $dossier->setSummary($data['summary']);
        $dossier->setTitle($data['title']);
        $dossier->setPublicationReason($data['publication_reason']);
        $dossier->setOrganisation($organisation);

        $this->mapDepartmentsToDossier($data, $dossier);

        $this->doctrine->persist($dossier);
        $this->doctrine->flush();

        $run = new InventoryProcessRun($dossier);
        $this->doctrine->persist($run);
        $this->doctrine->flush();

        $this->documentStorage->storeDocument(new \SplFileInfo($inventoryPath), $run);

        $this->runProcessor->process($run);

        if (! $run->isFinished()) {
            throw FixtureInventoryException::forProcessingErrors($run);
        }

        if ($documentsPath) {
            $documentPathFileInfo = new \SplFileInfo($documentsPath);
            $remotePath = '/uploads/' . $dossier->getId() . '/' . $documentPathFileInfo->getBasename();
            if (! $this->documentStorage->store($documentPathFileInfo, $remotePath)) {
                throw new \RuntimeException("Could not store document file from $remotePath to $documentsPath");
            }

            $message = new ProcessDocumentMessage(
                dossierUuid: $dossier->getId(),
                remotePath: $remotePath,
                originalFilename: $documentPathFileInfo->getBasename(),
                chunked: false,
            );

            $this->messageBus->dispatch($message);
        } else {
            // If no 'real' documents are provided: still index the metadata-only documents
            $options = new Options();
            foreach ($dossier->getDocuments() as $document) {
                $this->ingester->ingest($document, $options);
            }
        }

        if (isset($data['fake_documents']) && is_array($data['fake_documents'])) {
            foreach ($data['fake_documents'] as $fakeDocumentData) {
                $this->createDocument($dossier, $fakeDocumentData);
            }

            $this->doctrine->persist($dossier);
            $this->doctrine->flush();
        }

        return $dossier;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createDocument(Dossier $dossier, array $data): void
    {
        $data['document_id'] ??= strval(random_int(100000, 999999));
        $data['document_nr'] ??= 'PREF-' . $data['document_id'];
        $data['created_at'] = new \DateTimeImmutable($data['created_at'] ??= 'now');
        $data['updated_at'] = new \DateTimeImmutable($data['updated_at'] ??= 'now');
        $data['document_date'] = new \DateTimeImmutable($data['document_date'] ??= 'now');
        $data['pages'] ??= [];
        $data['source_type'] ??= SourceType::SOURCE_PDF;
        $data['family_id'] ??= $data['document_id'];
        $data['thread_id'] ??= 0;
        $data['summary'] ??= '';
        $data['uploaded'] ??= true;
        $data['filename'] ??= 'document-' . $data['document_nr'] . '.pdf';
        $data['mime_type'] ??= 'application/pdf';
        $data['file_type'] ??= 'pdf';
        $data['subjects'] ??= [];
        $data['suspended'] ??= false;
        $data['withdrawn'] ??= false;
        $data['judgement'] ??= Judgement::PUBLIC;

        $document = new Document();
        $document->setDocumentid($data['document_id']);
        $document->setDocumentNr($data['document_nr']);
        $document->setCreatedAt($data['created_at']);
        $document->setUpdatedAt($data['updated_at']);
        $document->setDocumentDate($data['document_date']);
        $document->setFamilyId($data['family_id']);
        $document->setThreadId($data['thread_id']);
        $document->setPageCount(count($data['pages']));
        $document->setSummary($data['summary']);
        $document->setSubjects($data['subjects']);
        $document->setSuspended($data['suspended']);
        $document->setJudgement($data['judgement']);

        $file = $document->getFileInfo();
        $file->setUploaded($data['uploaded']);
        $file->setName($data['filename']);
        $file->setMimetype($data['mime_type']);
        $file->setType($data['file_type']);
        $file->setSourceType($data['source_type']);

        $this->doctrine->persist($document);
        $dossier->addDocument($document);

        // Push the page keys starting from 1 instead of 0
        $pages = array_combine(range(1, count($data['pages'])), $data['pages']);

        $this->elasticService->updateDocument($document);
        $this->elasticService->setPages($document, $pages);
    }

    private function ensurePrefixExists(string $prefix): void
    {
        if ($this->doctrine->getRepository(DocumentPrefix::class)->count(['prefix' => $prefix]) === 0) {
            $organisation = $this->doctrine->getRepository(Organisation::class)->findAll()[0];

            $documentPrefix = new DocumentPrefix();
            $documentPrefix->setPrefix($prefix);
            $documentPrefix->setOrganisation($organisation);

            $this->doctrine->persist($documentPrefix);
            $this->doctrine->flush();
        }
    }

    private function mapDepartmentsToDossier(array $data, Dossier $dossier): void
    {
        if (! is_array($data['department'])) {
            $data['department'] = [$data['department']];
        }

        foreach ($data['department'] as $departmentName) {
            $department = $this->doctrine->getRepository(Department::class)->findOneBy(['name' => $departmentName]);
            if (! $department) {
                throw new \RuntimeException("Department $departmentName does not exist");
            }
            $dossier->addDepartment($department);
        }
    }
}
