<?php

declare(strict_types=1);

namespace Shared\Command;

use MinVWS\TypeArray\TypeArray;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Search\Index\ElasticDocumentId;
use Shared\Service\Elastic\ElasticService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

use function sprintf;

#[AsCommand(name: 'woopie:page:check', description: 'Checks if there are pages that are not yet indexed')]
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
        $this
            ->setHelp('Checks if there are pages that are not yet indexed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);
        $failed = false;

        $dossiers = $this->wooDecisionRepository->findAll();
        foreach ($dossiers as $dossier) {
            foreach ($dossier->getDocuments() as $document) {
                // Get the count from elastic
                $esDocument = $this->elasticService->getDocument(
                    ElasticDocumentId::forObject($document),
                );

                for ($i = 1; $i <= $document->getFileInfo()->getpageCount(); $i++) {
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

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    protected function pageExists(TypeArray $esDocument, int $pageNr): bool
    {
        foreach ($esDocument->getIterable('[_source][pages]') as $page) {
            Assert::isInstanceOf($page, TypeArray::class);

            if ($page->getInt('[page_nr]') == $pageNr) {
                return true;
            }
        }

        return false;
    }
}
