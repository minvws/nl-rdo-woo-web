<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Inquiry;
use App\Entity\Organisation;
use App\Service\Elastic\ElasticService;
use App\Service\FakeDataGenerator;
use App\Service\Inquiry\InquiryService;
use App\Service\Logging\LoggingHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GenerateDocuments extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly ElasticService $elasticService,
        private readonly FakeDataGenerator $fakeDataGenerator,
        private readonly LoggingHelper $loggingHelper,
        private readonly InquiryService $inquiryService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:generate:documents')
            ->setDescription('Test new elasticsearch layout')
            ->setHelp('Test new elasticsearch layout')
            ->setDefinition([
                new InputOption('dossiers', 'd', InputOption::VALUE_REQUIRED, 'Number of dossiers to generate', 50),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->loggingHelper->disableAll();

        $stopwatch = new Stopwatch();
        $stopwatch->start('generate-documents');

        $orgs = $this->doctrine->getRepository(Organisation::class)->findAll();
        shuffle($orgs);
        $organisation = reset($orgs);

        if (! $organisation instanceof Organisation) {
            throw new \RuntimeException('No organisations found');
        }

        $numberOfDossiers = intval($input->getOption('dossiers'));
        $inquiries = $this->generateInquiries($organisation);

        for ($i = 0; $i !== $numberOfDossiers; $i++) {
            $dossierInquiries = $this->pickInquiries($inquiries, $i, 3);

            print "Creating dossier $i / $numberOfDossiers\n";
            $dossier = $this->createDossier($organisation, 'DOSSIER-' . random_int(1000, 9999) . '-' . random_int(10000, 99999), $dossierInquiries);

            $docCount = random_int(10, 100);
            for ($j = 0; $j !== $docCount; $j++) {
                printf("  Creating document %04d / %04d\n", $j, $docCount);
                $this->addDocument(
                    $dossier,
                    $this->pickInquiries($inquiries, $j, 2),
                );

                print "\n";
            }

            $this->doctrine->flush();

            $this->doctrine->clear(Document::class);
        }

        $stopwatch->stop('generate-documents');
        $output->writeln($stopwatch->getEvent('generate-documents')->__toString());

        $this->loggingHelper->restoreAll();

        return 0;
    }

    /**
     * @return Inquiry[]
     */
    protected function generateInquiries(Organisation $organisation): array
    {
        $inquiries = [];

        for ($i = 0; $i < 10; $i++) {
            $caseNumber = (string) ($i + 100);
            $inquiries[] = $this->inquiryService->findOrCreateInquiryForCaseNumber($organisation, $caseNumber);
        }

        return $inquiries;
    }

    /**
     * Pick Inquiries which will be used for this Dossier and its Documents
     * Could return empty array to simulate Dossiers/Documents without Inquiries.
     *
     * @param Inquiry[] $inquiries
     *
     * @return Inquiry[]
     */
    protected function pickInquiries(array $inquiries, int $seed, int $maxReturn): array
    {
        if ($seed % 3 !== 0) {
            return [];
        }

        shuffle($inquiries);
        $randomInquiries = array_slice($inquiries, 0, rand(1, $maxReturn));

        return $randomInquiries;
    }

    /**
     * @param Inquiry[] $inquiries
     */
    protected function createDossier(Organisation $organisation, string $dossierNr, array $inquiries): Dossier
    {
        $dossier = $this->fakeDataGenerator->generateDossier($organisation, $dossierNr);

        foreach ($inquiries as $inquiry) {
            $dossier->addInquiry($inquiry);
        }
        $this->doctrine->persist($dossier);

        $this->elasticService->updateDossier($dossier, false);

        return $dossier;
    }

    /**
     * @param Inquiry[] $inquiries
     *
     * @throws \Exception
     */
    protected function addDocument(Dossier $dossier, array $inquiries): Document
    {
        // Add initial document to DB
        $document = $this->fakeDataGenerator->generateDocument();
        $document->addDossier($dossier);

        foreach ($inquiries as $inquiry) {
            $document->addInquiry($inquiry);
        }
        $this->doctrine->persist($document);

        // Generate all pages
        $pages = [];
        for ($k = 0; $k !== $document->getPageCount(); $k++) {
            printf("      Creating page %03d / %03d   %10d\r", $k, $document->getPageCount(), memory_get_usage(true));
            $content = $this->fakeDataGenerator->generateContent();

            $pages[] = [
                'page_nr' => $k + 1,
                'content' => $content,
            ];
        }

        // Index document and pages
        $this->elasticService->updateDocument($document, [], $pages);

        return $document;
    }
}
