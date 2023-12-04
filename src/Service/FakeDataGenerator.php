<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Department;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Judgement;
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
        shuffle($deps);
        $deps = array_slice($deps, 0, 2);

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
        $document->setDocumentDate(new \DateTimeImmutable());
        $document->setDocumentNr($documentNr);
        $document->setDuration(0);
        $document->setFamilyId($documentId);
        $document->setDocumentid($documentId);
        $document->setThreadId(0);
        $document->setPageCount(random_int(1, 20));
        $document->setSummary('summary of the document');
        $document->setSubjects($this->generateSubjects());

        $file = $document->getFileInfo();
        $file->setSourceType($sourceType);
        $file->setName('document-' . $documentNr . '.pdf');
        $file->setMimetype('application/pdf');
        $file->setType('pdf');

        switch ($randomInt = random_int(0, 10)) {
            case $randomInt <= 5:
                $document->setJudgement(Judgement::PUBLIC);
                $file->setUploaded(true);
                break;
            case $randomInt <= 7:
                $document->setJudgement(Judgement::PARTIAL_PUBLIC);
                $file->setUploaded(true);
                break;
            case $randomInt <= 8:
                $document->setJudgement(Judgement::ALREADY_PUBLIC);
                $file->setUploaded(false);
                break;
            default:
                $document->setJudgement(Judgement::NOT_PUBLIC);
                $file->setUploaded(false);
                break;
        }

        if (random_int(0, 1) === 1) {
            $document->setLink($this->faker->url());
        }

        if (random_int(0, 1) === 1) {
            $document->setRemark($this->faker->text());
        }

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
