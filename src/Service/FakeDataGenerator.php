<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Department;
use App\Entity\Organisation;
use App\SourceType;
use App\Tests\Faker\FakerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * Class that generates fake document/page/dossier data that can be used for debugging and development purposes.
 * This class is not used in production.
 *
 * @codeCoverageIgnore
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[When('dev')]
class FakeDataGenerator
{
    protected Generator $faker;

    public function __construct(protected EntityManagerInterface $doctrine)
    {
        $this->faker = FakerFactory::create();
    }

    public function generateDossier(Organisation $organisation, string $dossierNr): WooDecision
    {
        $deps = $this->doctrine->getRepository(Department::class)->findAll();
        shuffle($deps);
        $deps = array_slice($deps, 0, 2);

        /** @var string $sentences */
        $sentences = $this->faker->sentences(4, true);

        /** @var PublicationReason $reason */
        $reason = $this->faker->randomElement(PublicationReason::cases());

        /** @var DecisionType $decision */
        $decision = $this->faker->randomElement([
            DecisionType::ALREADY_PUBLIC,
            DecisionType::NOT_PUBLIC,
            DecisionType::NOTHING_FOUND,
            DecisionType::PARTIAL_PUBLIC,
            DecisionType::PARTIAL_PUBLIC,
            DecisionType::PARTIAL_PUBLIC,
            DecisionType::PARTIAL_PUBLIC,
            DecisionType::PUBLIC,
            DecisionType::PUBLIC,
            DecisionType::PUBLIC,
            DecisionType::PUBLIC,
        ]);

        $dossier = new WooDecision();
        $dossier->setDossierNr($dossierNr);
        $dossier->setTitle($this->faker->sentence());
        $dossier->setSummary($sentences);
        $dossier->setDocumentPrefix('PREF');
        $dossier->setPublicationReason($reason);
        $dossier->setDecision($decision);
        $dossier->setStatus(DossierStatus::PUBLISHED);
        $dossier->setOrganisation($organisation);
        foreach ($deps as $dep) {
            $dossier->addDepartment($dep);
        }

        $a = new \DateTimeImmutable('01-' . random_int(1, 12) . '-' . random_int(2010, 2023));
        $b = new \DateTimeImmutable('01-' . random_int(1, 12) . '-' . random_int(2010, 2023));
        if ($b < $a) {
            [$a, $b] = [$b, $a];
        }
        $dossier->setDateFrom($a);
        $dossier->setDateTo($b);
        $dossier->setDecisionDate($a);
        $dossier->setPublicationDate($b);

        return $dossier;
    }

    public function generateDocument(): Document
    {
        /** @var SourceType $sourceType */
        $sourceType = $this->faker->randomElement(SourceType::cases());
        $documentId = $this->faker->unique()->randomNumber(nbDigits: 6, strict: true);
        $documentNr = sprintf('PREF-%s', $documentId);
        $document = new Document();
        $document->setDocumentDate(new \DateTimeImmutable());
        $document->setDocumentNr($documentNr);
        $document->setFamilyId($documentId);
        $document->setDocumentId(strval($documentId));
        $document->setThreadId(0);
        $document->setPageCount(random_int(1, 20));
        $document->setSummary('summary of the document');
        $document->setGrounds($this->faker->groundsBetween());

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
            $document->setLinks([$this->faker->url()]);
        }

        if (random_int(0, 1) === 1) {
            $document->setRemark($this->faker->text());
        }

        return $document;
    }

    public function generateContent(): string
    {
        /** @var string */
        return $this->faker->sentences(random_int(20, 100), true);
    }
}
