<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Inquiry;
use App\Service\Elastic\ElasticService;
use App\Service\FakeDataGenerator;
use App\Service\Logging\LoggingHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Uid\Uuid;

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

        $numberOfDossiers = intval($input->getOption('dossiers'));
        $inquiries = $this->generateInquiries();

        for ($i = 0; $i !== $numberOfDossiers; $i++) {
            $dossierInquiries = $this->pickInquiries($inquiries, $i, 3);

            print "Creating dossier $i / $numberOfDossiers\n";
            $dossier = $this->createDossier('MINVWS-' . random_int(1000, 9999) . '-' . random_int(10000, 99999), $dossierInquiries);

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

            /* @phpstan-ignore-next-line */
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
    protected function generateInquiries(): array
    {
        $inquiries = [];

        for ($i = 0; $i < 10; $i++) {
            $now = new \DateTimeImmutable();

            $inquiry = new Inquiry();
            $inquiry->setCasenr((string) ($i + 100));
            $inquiry->setToken(Uuid::v6()->toBase58());
            $inquiry->setCreatedAt($now);
            $inquiry->setUpdatedAt($now);

            $this->doctrine->persist($inquiry);

            $inquiries[] = $inquiry;
        }

        $this->doctrine->flush();

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
    protected function createDossier(string $dossierNr, array $inquiries): Dossier
    {
        $dossier = $this->fakeDataGenerator->generateDossier($dossierNr);

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
