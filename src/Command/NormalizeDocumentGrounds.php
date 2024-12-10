<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NormalizeDocumentGrounds extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:normalize-document-grounds')
            ->setDefinition([
                new InputArgument('mapping', InputArgument::REQUIRED, 'The value mapping excel sheet'),
                new InputOption('dry-run', 'd', InputOption::VALUE_NONE, 'Dry run'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mapping = $this->loadMapping(strval($input->getArgument('mapping')));
        $dryRun = $input->getOption('dry-run');

        $dossierQuery = $this->entityManager->getRepository(Document::class)->createQueryBuilder('d')->select('d')->getQuery();
        $rowCount = 0;

        /** @var Document $document */
        foreach ($dossierQuery->toIterable() as $document) {
            $this->normalizeGrounds($output, $mapping, $document);

            $rowCount++;
            if ($rowCount % 1000 === 0) {
                if (! $dryRun) {
                    $this->entityManager->flush();
                }
                $this->entityManager->clear();
            }
        }

        if ($dryRun) {
            $output->writeln('<comment>WARNING: THIS IS A DRY RUN, NOTHING WAS UPDATED IN THE DATABASE</comment>');
        } else {
            $this->entityManager->flush();
        }

        $output->writeln(sprintf('<info>FINISHED, checked %d documents</info>', $rowCount));

        return 0;
    }

    /**
     * @param array<string, string[]> $mapping
     */
    private function normalizeGrounds(OutputInterface $output, array $mapping, Document $document): void
    {
        $currentGrounds = $document->getGrounds();
        $normalizedGrounds = [];
        foreach ($currentGrounds as $currentGround) {
            $currentGround = trim($currentGround);
            if (! array_key_exists($currentGround, $mapping)) {
                $normalizedGrounds[] = $currentGround;
                continue;
            }

            foreach ($mapping[$currentGround] as $mappedGround) {
                $normalizedGrounds[] = $mappedGround;
            }
        }

        $normalizedGrounds = array_unique($normalizedGrounds);
        if ($normalizedGrounds !== $currentGrounds) {
            $document->setGrounds($normalizedGrounds);
            $this->entityManager->persist($document);

            $output->writeln(sprintf(
                '<info>%s: updated [%s] to [%s]</info>',
                $document->getDocumentNr(),
                implode(';', $currentGrounds),
                implode(';', $normalizedGrounds),
            ));
        }
    }

    /**
     * @return array<string, string[]>
     */
    private function loadMapping(string $filename): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filename);

        $rows = $spreadsheet->getActiveSheet()->toArray();
        unset($rows[0]); // Skip header

        $mapping = [];
        foreach ($rows as $row) {
            if ($row[0] === null) {
                continue;
            }

            if ($row[1] === null) {
                $normalizedValues = [];
            } else {
                $normalizedValues = explode(';', $row[1]);
                $normalizedValues = array_map('trim', $normalizedValues);
            }
            $mapping[trim($row[0])] = $normalizedValues;
        }

        return $mapping;
    }
}
