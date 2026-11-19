<?php

declare(strict_types=1);

namespace Shared\Tests\Story;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use InvalidArgumentException;
use RuntimeException;
use Shared\Domain\Department\Department;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\DossierService;
use Shared\Service\DossierWizard\WizardStatusFactory;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\InquiryFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\ProductionReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Advice\AdviceAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Advice\AdviceFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Advice\AdviceMainDocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantMainDocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionMainDocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocumentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Tests\Story\Demo\DemoFileWriter;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Story;

use function array_map;
use function array_rand;
use function array_slice;
use function ceil;
use function count;
use function filter_var;
use function fwrite;
use function getenv;
use function implode;
use function max;
use function memory_get_usage;
use function random_int;
use function range;
use function shuffle;
use function sprintf;
use function Zenstruck\Foundry\faker;

use const FILTER_VALIDATE_INT;
use const STDERR;

/**
 * @phpstan-type OtherDossierType array{
 *     class-string<PersistentObjectFactory<covariant AbstractDossier>>,
 *     class-string<PersistentObjectFactory<covariant object>>,
 *     class-string<PersistentObjectFactory<covariant AbstractAttachment>>|null,
 * }
 *
 * Fills a development database with dossiers of every type and status, including generated (multi page) files.
 *
 * The number of dossiers is read from the DEMO_DATA_DOSSIERS environment variable (0 to 100000, default 95). The default
 * set contains every combination of dossier type and status twice, and a dossier with many documents. A different number
 * repeats or truncates that set.
 *
 * Load with: task app:loaddemodata (see docs/demo-data.md)
 */
#[AsFixture(name: 'demo')]
final class DemoDataStory extends Story
{
    public const string COUNT_VARIABLE = 'DEMO_DATA_DOSSIERS';
    public const int DEFAULT_DOSSIERS = 95;
    public const int MAX_DOSSIERS = 100000;

    private const int DOCUMENTS_MIN = 4;
    private const int DOCUMENTS_MAX = 20;
    private const int LARGE_DOSSIER_DOCUMENTS = 150;
    private const int FIRST_DOCUMENT_ID = 100000;
    private const int PROGRESS_INTERVAL = 25;
    private const int INQUIRY_EVERY_N_WOO_DECISIONS = 5;
    private const int INQUIRY_DOSSIERS = 3;
    private const int MAX_REPORTED_INCOMPLETE = 25;
    private const float SECONDS_PER_DOSSIER = 0.1;

    /** Page counts by document index, used for the first documents of the large dossier. */
    private const array LONG_DOCUMENT_PAGES = [0 => 60, 1 => 40, 2 => 25];

    private int $dossierCounter = 0;
    private int $documentCounter = 0;
    private int $wooDecisionCounter = 0;
    private int $inquiryCounter = 0;
    private int $incompleteCount = 0;

    private Uuid $organisationId;

    /** @var list<Uuid> */
    private array $departmentIds = [];

    /** @var list<Uuid> */
    private array $subjectIds = [];

    /** @var list<Uuid> */
    private array $recentWooDecisionIds = [];

    /** @var list<string> */
    private array $incomplete = [];

    public function __construct(
        private readonly DossierService $dossierService,
        private readonly WizardStatusFactory $wizardStatusFactory,
        private readonly DemoFileWriter $files,
        private readonly IngestDispatcher $ingestDispatcher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function build(): void
    {
        $count = $this->requestedCount();
        $this->guardAgainstExistingDemoData();

        if ($count === 0) {
            return;
        }

        $this->announce($count);

        $this->organisationId = OrganisationFactory::first()->getId();
        $this->departmentIds = array_map(static fn (Department $department) => $department->getId(), DepartmentFactory::all());
        $this->subjectIds = array_map(static fn (Subject $subject) => $subject->getId(), SubjectFactory::all());

        $created = 0;
        foreach ($this->scenarios($count) as $scenario) {
            $scenario();

            // Keeps memory flat, the factories and the story only work with ids from here on.
            $this->entityManager->clear();
            faker()->unique(true);

            $created++;
            if ($created % self::PROGRESS_INTERVAL === 0 || $created === $count) {
                fwrite(STDERR, sprintf("Created %d of %d dossiers (%d MB memory)\n", $created, $count, memory_get_usage(true) / 1048576));
            }
        }

        $this->reportIncomplete();
    }

    private function requestedCount(): int
    {
        $value = getenv(self::COUNT_VARIABLE);
        if ($value === false || $value === '') {
            return self::DEFAULT_DOSSIERS;
        }

        $count = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => self::MAX_DOSSIERS]]);
        if ($count === false) {
            throw new InvalidArgumentException(sprintf(
                '%s must be a whole number between 0 and %d, "%s" given.',
                self::COUNT_VARIABLE,
                self::MAX_DOSSIERS,
                $value,
            ));
        }

        return $count;
    }

    private function guardAgainstExistingDemoData(): void
    {
        $existing = (int) $this->entityManager
            ->createQuery(sprintf("SELECT COUNT(d.id) FROM %s d WHERE LOWER(d.dossierNumber) LIKE 'demo-%%'", AbstractDossier::class))
            ->getSingleScalarResult();

        if ($existing > 0) {
            throw new RuntimeException(sprintf(
                'Demo data is already loaded (%d demo dossiers found). Use "task app:loaddemodata", which clears the data first.',
                $existing,
            ));
        }
    }

    private function announce(int $count): void
    {
        $minutes = max(1, (int) ceil($count * self::SECONDS_PER_DOSSIER / 60));
        $documents = (int) ($count * (self::DOCUMENTS_MIN + self::DOCUMENTS_MAX) / 4);

        fwrite(STDERR, sprintf(
            "Creating %d dossiers with about %d documents and generated PDFs, this takes roughly %d minute(s).\n",
            $count,
            $documents,
            $minutes,
        ));
    }

    /**
     * Every combination of dossier type and status once (Woo decisions and other types alternating), followed by one dossier with
     * many documents, after which the combinations repeat. Repeats use the other variant, which adds special cases to the documents.
     *
     * @return Generator<int, Closure(): void>
     */
    private function scenarios(int $count): Generator
    {
        $wooDecisionStatuses = [DossierStatus::CONCEPT, DossierStatus::SCHEDULED, DossierStatus::PREVIEW, DossierStatus::PUBLISHED];
        $otherStatuses = [DossierStatus::CONCEPT, DossierStatus::SCHEDULED, DossierStatus::PUBLISHED];

        $wooDecisions = [];
        foreach (DecisionType::cases() as $decision) {
            foreach ($wooDecisionStatuses as $status) {
                $wooDecisions[] = [$decision, $status];
            }
        }

        $others = [];
        foreach ($this->otherDossierTypes() as $type) {
            foreach ($otherStatuses as $status) {
                $others[] = [$type, $status];
            }
        }

        $produced = 0;
        for ($round = 0; $produced < $count; $round++) {
            for ($i = 0; $i < max(count($wooDecisions), count($others)); $i++) {
                if ($i < count($wooDecisions) && $produced++ < $count) {
                    [$decision, $status] = $wooDecisions[$i];

                    yield fn () => $this->buildWooDecision($decision, $status, $round);
                }

                if ($i < count($others) && $produced++ < $count) {
                    [$type, $status] = $others[$i];

                    yield fn () => $this->buildOtherDossier($type, $status);
                }
            }

            if ($round === 0 && $produced++ < $count) {
                yield fn () => $this->buildWooDecision(DecisionType::PUBLIC, DossierStatus::PUBLISHED, 0, self::LARGE_DOSSIER_DOCUMENTS);
            }
        }
    }

    private function buildWooDecision(DecisionType $decision, DossierStatus $status, int $variant, ?int $documentCount = null): void
    {
        $dossier = WooDecisionFactory::new()->skipInverseWiringFor('organisation')->skipInverseWiringFor('subject')->create([
            'organisation' => $this->organisation(),
            'dossierNumber' => $this->nextDossierNumber('WOO'),
            'decision' => $decision,
            'departments' => $this->randomDepartments(),
            'subject' => $this->randomSubject(),
            'document' => WooDecisionMainDocumentFactory::new(),
        ] + $this->statusAttributes($status, true));

        $this->writeMainDocumentAndAttachments($dossier, WooDecisionAttachmentFactory::class);

        $documents = [];
        if ($decision !== DecisionType::NOTHING_FOUND) {
            $documents = $this->createDocuments(
                $dossier,
                $documentCount ?? random_int(self::DOCUMENTS_MIN, self::DOCUMENTS_MAX),
                $status,
                $variant % 2 === 1,
            );
        }

        if ($dossier->isInventoryRequired() || ($dossier->isInventoryOptional() && $variant % 2 === 1)) {
            $report = ProductionReportFactory::createOne(['dossier' => $dossier]);
            $this->files->writeSpreadsheet($report, $documents);
        }

        $this->finish($dossier);
        $this->createInquiryWhenDue($dossier, $documents);
    }

    /**
     * @return list<Document>
     */
    private function createDocuments(WooDecision $dossier, int $count, DossierStatus $status, bool $withSpecialCases): array
    {
        $documents = [];
        $familyId = null;

        for ($i = 0; $i < $count; $i++) {
            $documentId = self::FIRST_DOCUMENT_ID + ++$this->documentCounter;

            // Every fifth document starts a new family of related documents.
            if ($i % 5 === 0) {
                $familyId = $documentId;
            }

            $factory = DocumentFactory::new();

            if ($withSpecialCases && $i === 0) {
                $factory = $factory->withdrawn();
            }

            $document = $factory->create([
                'dossiers' => [$dossier],
                'documentId' => (string) $documentId,
                'familyId' => $familyId,
                'threadId' => $i % 7 === 0 ? 0 : random_int(1, 3),
            ]);

            if ($withSpecialCases && $i === 1) {
                $document->setSuspended(true);
            }

            // Documents of a concept dossier still miss their file now and then, to test incomplete uploads.
            $missingFile = $status === DossierStatus::CONCEPT && $i % 4 === 3;

            if ($document->getFileInfo()->isUploaded() && ! $missingFile) {
                // A few long documents, to test the page viewer with many pages.
                $pages = $count === self::LARGE_DOSSIER_DOCUMENTS ? self::LONG_DOCUMENT_PAGES[$i] ?? null : null;
                $this->files->writePdf($document, sprintf('Document %s', $document->getDocumentNumber()->toString()), $pages);
            } elseif ($missingFile) {
                $document->getFileInfo()->removeFileProperties();
            }

            $documents[] = $document;
        }

        return $documents;
    }

    /**
     * @return list<OtherDossierType>
     */
    private function otherDossierTypes(): array
    {
        return [
            [CovenantFactory::class, CovenantMainDocumentFactory::class, CovenantAttachmentFactory::class],
            [AnnualReportFactory::class, AnnualReportMainDocumentFactory::class, AnnualReportAttachmentFactory::class],
            [InvestigationReportFactory::class, InvestigationReportMainDocumentFactory::class, InvestigationReportAttachmentFactory::class],
            [DispositionFactory::class, DispositionMainDocumentFactory::class, DispositionAttachmentFactory::class],
            [ComplaintJudgementFactory::class, ComplaintJudgementMainDocumentFactory::class, null],
            [OtherPublicationFactory::class, OtherPublicationMainDocumentFactory::class, OtherPublicationAttachmentFactory::class],
            [AdviceFactory::class, AdviceMainDocumentFactory::class, AdviceAttachmentFactory::class],
            [RequestForAdviceFactory::class, RequestForAdviceMainDocumentFactory::class, RequestForAdviceAttachmentFactory::class],
            [DraftDecisionFactory::class, DraftDecisionMainDocumentFactory::class, DraftDecisionAttachmentFactory::class],
        ];
    }

    /**
     * @param OtherDossierType $type
     */
    private function buildOtherDossier(array $type, DossierStatus $status): void
    {
        [$dossierFactory, $mainDocumentFactory, $attachmentFactory] = $type;

        $dossier = $dossierFactory::new()->skipInverseWiringFor('organisation')->skipInverseWiringFor('subject')->create([
            'organisation' => $this->organisation(),
            'dossierNumber' => $this->nextDossierNumber('DOS'),
            'departments' => $this->randomDepartments(),
            'subject' => $this->randomSubject(),
            'document' => $mainDocumentFactory::new(),
            'dateFrom' => faker()->plainDateBetween('-5 years', '-1 month'),
        ] + $this->typeAttributes($dossierFactory) + $this->statusAttributes($status, false));

        $this->writeMainDocumentAndAttachments($dossier, $attachmentFactory);
        $this->finish($dossier);
    }

    /**
     * @param class-string<PersistentObjectFactory<covariant AbstractDossier>> $dossierFactory
     *
     * @return array<string, mixed>
     */
    private function typeAttributes(string $dossierFactory): array
    {
        return match ($dossierFactory) {
            CovenantFactory::class => ['parties' => array_map(static fn () => faker()->company(), range(1, random_int(2, 5)))],
            RequestForAdviceFactory::class => [
                'link' => faker()->url(),
                'advisoryBodies' => [faker()->company()],
            ],
            default => [],
        };
    }

    /**
     * @param class-string<PersistentObjectFactory<covariant AbstractAttachment>>|null $attachmentFactory
     */
    private function writeMainDocumentAndAttachments(AbstractDossier $dossier, ?string $attachmentFactory): void
    {
        if ($dossier instanceof EntityWithMainDocument && $dossier->getMainDocument() instanceof AbstractMainDocument) {
            $this->files->writePdf($dossier->getMainDocument(), sprintf('Hoofddocument %s', $dossier->getTitle()));
        }

        if ($attachmentFactory === null || ! $dossier instanceof EntityWithAttachments) {
            return;
        }

        $required = $dossier instanceof DraftDecision;

        for ($i = 1, $count = random_int($required ? 1 : 0, 4); $i <= $count; $i++) {
            $attributes = $required && $i === 1 ? ['type' => AttachmentType::REQUEST_FOR_ADVICE] : [];
            $attachment = $attachmentFactory::new()->create(['dossier' => $dossier] + $attributes);
            $dossier->addAttachment($attachment);
            $this->files->writePdf($attachment, sprintf('Bijlage %d bij %s', $i, $dossier->getTitle()));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function statusAttributes(DossierStatus $status, bool $withPreview): array
    {
        $future = faker()->plainDateBetween('+1 week', '+2 weeks');
        $past = faker()->plainDateBetween('-2 weeks', '-1 week');

        return match ($status) {
            DossierStatus::CONCEPT => ['status' => $status, 'publicationDate' => $future],
            DossierStatus::SCHEDULED => ['status' => $status, 'publicationDate' => $future] + ($withPreview ? ['previewDate' => $future] : []),
            DossierStatus::PREVIEW => ['status' => $status, 'publicationDate' => $future, 'previewDate' => $past],
            default => ['status' => $status, 'publicationDate' => $past] + ($withPreview ? ['previewDate' => $past] : []),
        };
    }

    private function finish(AbstractDossier $dossier): void
    {
        $completed = $this->dossierService->validateCompletion($dossier);

        if (! $completed && ! $dossier->getStatus()->isConcept()) {
            $this->registerIncomplete($dossier);
        }

        // Dispatched once the dossier is flushed, so the workers can find everything they need.
        foreach ($this->files->drainStoredPdfs() as $entity) {
            $this->ingestDispatcher->dispatchIngestPdfCommand($entity, false);
        }

        if ($dossier->getStatus()->isPubliclyAvailable()) {
            $this->ingestDispatcher->dispatchIngestDossierCommand($dossier, true);
        }
    }

    private function registerIncomplete(AbstractDossier $dossier): void
    {
        $this->incompleteCount++;

        if (count($this->incomplete) >= self::MAX_REPORTED_INCOMPLETE) {
            return;
        }

        $steps = [];
        foreach ($this->wizardStatusFactory->getWizardStatus($dossier, StepName::DETAILS, false)->getSteps() as $step) {
            if (! $step->isCompleted()) {
                $steps[] = $step->getStepName()->value;
            }
        }

        $this->incomplete[] = sprintf(
            '%s %s (%s): %s',
            $dossier->getType()->value,
            $dossier->getDossierNumber(),
            $dossier->getStatus()->value,
            implode(', ', $steps),
        );
    }

    private function reportIncomplete(): void
    {
        if ($this->incompleteCount === 0) {
            return;
        }

        fwrite(STDERR, sprintf("%d non-concept dossiers are not complete:\n%s\n", $this->incompleteCount, implode("\n", $this->incomplete)));
    }

    /**
     * Links every few Woo decisions to the ones before them with an inquiry, with an inventory file.
     *
     * @param list<Document> $documents
     */
    private function createInquiryWhenDue(WooDecision $dossier, array $documents): void
    {
        $this->recentWooDecisionIds[] = $dossier->getId();
        $this->recentWooDecisionIds = array_slice($this->recentWooDecisionIds, -self::INQUIRY_DOSSIERS);

        if (++$this->wooDecisionCounter % self::INQUIRY_EVERY_N_WOO_DECISIONS !== 0) {
            return;
        }

        $dossiers = [$dossier];
        $inquiryDocuments = $this->pickSome($documents);
        foreach ($this->recentWooDecisionIds as $id) {
            if ($id->equals($dossier->getId())) {
                continue;
            }

            $other = $this->entityManager->find(WooDecision::class, $id);
            if ($other instanceof WooDecision) {
                $dossiers[] = $other;
                $inquiryDocuments = [...$inquiryDocuments, ...$this->pickSome($other->getDocuments()->toArray())];
            }
        }

        $inquiryNumber = sprintf('ZAAK-%06d', ++$this->inquiryCounter);
        $inquiry = InquiryFactory::new()->skipInverseWiringFor('organisation')->create([
            'organisation' => $this->organisation(),
            'inquiryNumber' => $inquiryNumber,
            'dossiers' => $dossiers,
            'documents' => $inquiryDocuments,
        ]);

        $inventory = $inquiry->getInventory();
        if ($inventory !== null) {
            $this->files->writeSpreadsheet($inventory, $inquiryDocuments, sprintf('inventaris-%s.xlsx', $inquiryNumber));
        }

        $this->entityManager->flush();
    }

    /**
     * @param array<array-key, Document> $documents
     *
     * @return list<Document>
     */
    private function pickSome(array $documents): array
    {
        $picked = [];
        foreach ($documents as $document) {
            if (random_int(0, 2) === 0) {
                $picked[] = $document;
            }
        }

        return $picked;
    }

    private function nextDossierNumber(string $prefix): string
    {
        return sprintf('DEMO-%s-%06d', $prefix, ++$this->dossierCounter);
    }

    /**
     * The dossier factories skip the inverse wiring of the organisation and the subject: Foundry would otherwise load all existing
     * dossiers of the organisation to add the new one to its collection, which makes every dossier slower than the one before it.
     */
    private function organisation(): Organisation
    {
        $organisation = $this->entityManager->getReference(Organisation::class, $this->organisationId);
        Assert::notNull($organisation);

        return $organisation;
    }

    /**
     * @return list<Department>
     */
    private function randomDepartments(): array
    {
        $ids = $this->departmentIds;
        shuffle($ids);

        $departments = [];
        foreach (array_slice($ids, 0, random_int(1, 2)) as $id) {
            $department = $this->entityManager->getReference(Department::class, $id);
            Assert::notNull($department);
            $departments[] = $department;
        }

        return $departments;
    }

    private function randomSubject(): ?Subject
    {
        if ($this->subjectIds === []) {
            return null;
        }

        return $this->entityManager->getReference(Subject::class, $this->subjectIds[array_rand($this->subjectIds)]);
    }
}
