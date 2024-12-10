<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Publication\Dossier\Type\WooDecision\Repository\WooDecisionRepository;
use App\Service\Elastic\ElasticService;
use Jaytaph\TypeArray\TypeArray;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PageCheck extends Command
{
    public function __construct(
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly ElasticService $elasticService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:page:check')
            ->setDescription('Checks if there are pages that are not yet indexed')
            ->setHelp('Checks if there are pages that are not yet indexed')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);
        $failed = false;

        $dossiers = $this->wooDecisionRepository->findAll();
        foreach ($dossiers as $dossier) {
            foreach ($dossier->getDocuments() as $document) {
                // Get the count from elastic
                $esDocument = $this->elasticService->getDocument($document->getDocumentNr());

                for ($i = 1; $i <= $document->getpageCount(); $i++) {
                    if (! $this->pageExists($esDocument, $i)) {
                        $output->writeln(sprintf(
                            'Dossier %s Document %s Page %d does not exist in elastic...',
                            $dossier->getDossierNr(),
                            $document->getDocumentNr(),
                            $i
                        ));
                        $failed = true;
                    }
                }
            }
        }

        return $failed ? 1 : 0;
    }

    protected function pageExists(TypeArray $esDocument, int $pageNr): bool
    {
        foreach ($esDocument->getIterable('[_source][pages]') as $page) {
            if ($page->getInt('[page_nr]') == $pageNr) {
                return true;
            }
        }

        return false;
    }
}
