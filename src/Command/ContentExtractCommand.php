<?php

declare(strict_types=1);

namespace Shared\Command;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Ingest\Content\ContentExtractOptions;
use Shared\Domain\Ingest\Content\ContentExtractService;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\EntityWithFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class ContentExtractCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContentExtractService $contentExtractService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:dev:extract-content')
            ->setDescription('Extracts content for an entity using Tika and Tesseract')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            'Please select an entity class',
            [
                Document::class,
                WooDecisionMainDocument::class,
                WooDecisionAttachment::class,
                CovenantMainDocument::class,
                CovenantAttachment::class,
                AnnualReportAttachment::class,
                AnnualReportMainDocument::class,
                InvestigationReportMainDocument::class,
                InvestigationReportAttachment::class,
                DispositionMainDocument::class,
                DispositionAttachment::class,
                ComplaintJudgementMainDocument::class,
            ],
            0
        );
        $question->setErrorMessage('Choice %s is invalid.');
        /** @var class-string<EntityWithFileInfo> $entityClass */
        $entityClass = $helper->ask($input, $output, $question);

        $question = new Question('Please enter the uuid of the entity: ');
        $entityId = $helper->ask($input, $output, $question);

        /** @var EntityWithFileInfo $entity */
        $entity = $this->entityManager->getRepository($entityClass)->find($entityId);
        if (! $entity) {
            $output->writeln('<error>Could not load entity from database</error>');

            return 1;
        }

        $extracts = $this->contentExtractService->getExtracts(
            $entity,
            ContentExtractOptions::create()->withAllExtractors(),
        );

        $io = new SymfonyStyle($input, $output);
        $io->newLine(2);
        foreach ($extracts as $extract) {
            $io->section($extract->key->value);
            $io->text('Date: ' . $extract->date->format('d-m-Y H:i:s'));
            $io->text('Content:');
            $io->text($extract->content);
            $io->newLine(5);
        }

        return 0;
    }
}
