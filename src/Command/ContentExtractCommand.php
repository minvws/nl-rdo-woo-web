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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\Assert\Assert;

#[AsCommand(name: 'woopie:dev:extract-content', description: 'Extracts content for an entity using Tika and Tesseract')]
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        Assert::isInstanceOf($helper, QuestionHelper::class);

        $choices = $this->getChoices();
        $question = new ChoiceQuestion('Please select an entity class', $choices, 0);
        $question->setErrorMessage('Choice %s is invalid.');
        $entityClass = $helper->ask($input, $output, $question);

        Assert::classExists($entityClass);
        Assert::implementsInterface($entityClass, EntityWithFileInfo::class);

        $question = new Question('Please enter the uuid of the entity: ');
        $entityId = $helper->ask($input, $output, $question);

        $entity = $this->entityManager->getRepository($entityClass)->find($entityId);
        if (! $entity instanceof EntityWithFileInfo) {
            $output->writeln('<error>Could not load entity from database</error>');

            return self::FAILURE;
        }
        Assert::isAnyOf($entity::class, $choices);

        $io = new SymfonyStyle($input, $output);
        $io->newLine(2);
        $extracts = $this->contentExtractService->getExtracts($entity, ContentExtractOptions::create()->withAllExtractors());
        foreach ($extracts as $extract) {
            $io->section($extract->key->value);
            $io->text('Date: ' . $extract->date->format('d-m-Y H:i:s'));
            $io->text('Content:');
            $io->text($extract->content);
            $io->newLine(5);
        }

        return self::SUCCESS;
    }

    /**
     * @return list<class-string<EntityWithFileInfo>>
     */
    private function getChoices(): array
    {
        return [
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
        ];
    }
}
