<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Document;
use App\Entity\Dossier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

class Where extends Command
{
    protected EntityManagerInterface $doctrine;
    protected UrlMatcherInterface $matcher;

    public function __construct(EntityManagerInterface $doctrine, UrlMatcherInterface $matcher)
    {
        parent::__construct();

        $this->doctrine = $doctrine;
        $this->matcher = $matcher;
    }

    protected function configure(): void
    {
        $this->setName('woopie:where')
            ->setDescription('Returns path information about a URL')
            ->setHelp('Returns path information about a URL')
            ->setDefinition([
                new InputArgument('url', InputArgument::REQUIRED, 'url to parse'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var array<string, string> $parts */
        $parts = parse_url(strval($input->getArgument('url')));

        try {
            $match = $this->matcher->match($parts['path']);
        } catch (\Exception) {
            $output->writeln("<error>Could not match {$parts['path']}</error>");

            return 1;
        }

        $output->writeln("<info>Matched {$parts['path']} to {$match['_route']}</info>");

        if (! isset($match['dossierId'])) {
            $output->writeln('<error>No dossierId found</error>');

            return 1;
        }

        $dossier = $this->doctrine->getRepository(Dossier::class)->findOneBy(['dossierNr' => $match['dossierId']]);
        if (! $dossier) {
            $output->writeln("<error>Dossier {$match['dossierId']} not found</error>");

            return 1;
        }

        if (isset($match['documentId'])) {
            $document = $this->doctrine->getRepository(Document::class)->findOneBy(['documentNr' => $match['documentId']]);
            $documents = [$document];
        } else {
            $documents = $dossier->getDocuments();
        }

        foreach ($documents as $document) {
            if (! $document) {
                continue;
            }
            $output->writeln("Document : <info>{$document->getId()}</info>");
            $output->writeln("Filename : <info>{$document->getFilename()}</info>");
            $output->writeln("Path     : <info>{$document->getFilepath()}</info>");
            $output->writeln('');
        }

        return 0;
    }
}
