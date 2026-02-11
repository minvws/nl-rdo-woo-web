<?php

declare(strict_types=1);

namespace Shared\Command;

use Exception;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Webmozart\Assert\Assert;

use function parse_url;

#[AsCommand(name: 'woopie:where', description: 'Returns path information about a URL')]
class Where extends Command
{
    public function __construct(
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly UrlMatcherInterface $matcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Returns path information about a URL')
            ->setDefinition([
                new InputArgument('url', InputArgument::REQUIRED, 'url to parse'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $url = $input->getArgument('url');
        Assert::string($url);

        $parts = parse_url($url);
        Assert::isMap($parts);
        Assert::keyExists($parts, 'path');

        try {
            $match = $this->matcher->match($parts['path']);
            Assert::string($match['_route']);
            Assert::string($match['dossierId']);
        } catch (Exception) {
            $output->writeln("<error>Could not match {$parts['path']}</error>");

            return self::FAILURE;
        }

        $output->writeln("<info>Matched {$parts['path']} to {$match['_route']}</info>");

        if (! isset($match['dossierId'])) {
            $output->writeln('<error>No dossierId found</error>');

            return self::FAILURE;
        }

        $dossier = $this->wooDecisionRepository->findOneBy(['dossierNr' => $match['dossierId']]);
        if (! $dossier) {
            $output->writeln("<error>Dossier {$match['dossierId']} not found</error>");

            return self::FAILURE;
        }

        if (isset($match['documentId'])) {
            $document = $this->documentRepository->findOneBy(['documentNr' => $match['documentId']]);
            $documents = [$document];
        } else {
            $documents = $dossier->getDocuments();
        }

        foreach ($documents as $document) {
            if (! $document) {
                continue;
            }
            $output->writeln("Document : <info>{$document->getId()}</info>");
            $output->writeln("Filename : <info>{$document->getFileInfo()->getName()}</info>");
            $output->writeln("Path     : <info>{$document->getFileInfo()->getPath()}</info>");
            $output->writeln('');
        }

        return self::SUCCESS;
    }
}
