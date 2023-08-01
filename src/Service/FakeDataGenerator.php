<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Department;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\GovernmentOfficial;
use App\SourceType;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;

/**
 * Class that generates fake document/page/dossier data that can be used for debugging and development purposes.
 * This class is not used in production.
 */
class FakeDataGenerator
{
    protected Generator $faker;
    protected EntityManagerInterface $doctrine;

    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->faker = Factory::create('nl_NL');
    }

    public function generateDossier(string $dossierNr): Dossier
    {
        $deps = $this->doctrine->getRepository(Department::class)->findAll();
        $officials = $this->doctrine->getRepository(GovernmentOfficial::class)->findAll();

        shuffle($deps);
        $deps = array_slice($deps, 0, 2);
        shuffle($officials);
        $officials = array_slice($officials, 0, 2);

        /** @var string $sentences */
        $sentences = $this->faker->sentences(4, true);

        /** @var string $reason */
        $reason = $this->faker->randomElement([
            \App\Entity\Dossier::REASON_WOB_REQUEST,
            Dossier::REASON_WOO_REQUEST,
            Dossier::REASON_WOO_ACTIVE,
        ]);

        /** @var string $decision */
        $decision = $this->faker->randomElement([
            Dossier::DECISION_ALREADY_PUBLIC,
            Dossier::DECISION_NOT_PUBLIC,
            Dossier::DECISION_NOTHING_FOUND,
            Dossier::DECISION_PARTIAL_PUBLIC,
            Dossier::DECISION_PARTIAL_PUBLIC,
            Dossier::DECISION_PARTIAL_PUBLIC,
            Dossier::DECISION_PARTIAL_PUBLIC,
            Dossier::DECISION_PUBLIC,
            Dossier::DECISION_PUBLIC,
            Dossier::DECISION_PUBLIC,
            Dossier::DECISION_PUBLIC,
        ]);

        $dossier = new Dossier();
        $dossier->setCreatedAt(new \DateTimeImmutable());
        $dossier->setUpdatedAt(new \DateTimeImmutable());
        $dossier->setDossierNr($dossierNr);
        $dossier->setTitle($this->faker->sentence());
        $dossier->setSummary($sentences);
        $dossier->setDocumentPrefix('PREF');
        $dossier->setPublicationReason($reason);
        $dossier->setDecision($decision);
        $dossier->setStatus(Dossier::STATUS_PUBLISHED);
        foreach ($deps as $dep) {
            $dossier->addDepartment($dep);
        }
        foreach ($officials as $official) {
            $dossier->addGovernmentOfficial($official);
        }

        $a = new \DateTimeImmutable('01-' . random_int(1, 12) . '-' . random_int(2010, 2023));
        $b = new \DateTimeImmutable('01-' . random_int(1, 12) . '-' . random_int(2010, 2023));
        if ($b < $a) {
            list($a, $b) = [$b, $a];
        }
        $dossier->setDateFrom($a);
        $dossier->setDateTo($b);

        return $dossier;
    }

    public function generateDocument(): Document
    {
        /** @var string $sourceType */
        $sourceType = $this->faker->randomElement(SourceType::getAllSourceTypes());

        $documentId = random_int(100000, 999999);
        $documentNr = 'PREF-' . $documentId;
        $document = new Document();
        $document->setCreatedAt(new \DateTimeImmutable());
        $document->setUpdatedAt(new \DateTimeImmutable());
        $document->setDocumentDate(new \DateTimeImmutable());
        $document->setDocumentNr($documentNr);
        $document->setSourceType($sourceType);
        $document->setDuration(0);
        $document->setFamilyId($documentId);
        $document->setDocumentid($documentId);
        $document->setThreadId(0);
        $document->setPageCount(random_int(1, 20));
        $document->setSummary('summary of the document');
        $document->setUploaded(false);
        $document->setFilename('document-' . $documentNr . '.pdf');
        $document->setMimetype('application/pdf');
        $document->setFileType('pdf');
        $document->setSubjects($this->generateSubjects());
        $document->setSuspended(false);
        $document->setWithdrawn(false);

        return $document;
    }

    /**
     * @return string[]
     */
    protected function generateSubjects(): array
    {
        /** @var string[] $words */
        $words = $this->faker->words(random_int(1, 5));

        return $words;
    }

    public function generateContent(): string
    {
        /** @var string $string */
        $string = $this->faker->sentences(random_int(20, 100), true);

        return $string;
    }

    public function generateSentences(): string
    {
        /** @var string $string */
        $string = $this->faker->sentences(random_int(20, 100), true);

        return $string;
    }
}
