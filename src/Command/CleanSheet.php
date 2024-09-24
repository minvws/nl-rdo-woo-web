<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Subject\Subject;
use App\Entity\BatchDownload;
use App\Entity\Document;
use App\Entity\DocumentPrefix;
use App\Entity\History;
use App\Entity\Inquiry;
use App\Entity\User;
use App\Service\Elastic\IndexService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
#[When('dev')]
class CleanSheet extends Command
{
    /**
     * @param string[] $queueDsns
     */
    public function __construct(
        private readonly array $queueDsns,
        private readonly EntityManagerInterface $entityManager,
        private readonly IndexService $indexService,
        private readonly HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:dev:clean-sheet')
            ->setDescription('Resets data from search index, database and message queue.')
            ->setHelp('Resets data from search index, database and message queue')
            ->setDefinition([
                new InputOption('force', null, InputOption::VALUE_NONE, 'Force the operation without confirmation'),
                new InputOption('users', 'u', InputOption::VALUE_NONE, 'Reset users'),
                new InputOption('prefixes', 'p', InputOption::VALUE_NONE, 'Reset prefixes'),
                new InputOption('index', 'i', InputOption::VALUE_REQUIRED, 'ES index name', 'woopie'),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $shouldForce = $input->getOption('force');
        $io = new SymfonyStyle($input, $output);
        if (! $shouldForce && ! $io->confirm('Are you REALLY sure you want to clear data from the system?', false)) {
            $output->writeln('Cancelled execution, no data has been removed');

            return 0;
        }

        $this->clearQueues($output);

        $indexName = strval($input->getOption('index'));
        $this->removeElasticSearchIndex($indexName, $output);
        $this->createElasticSearchIndex($indexName, $output);

        $this->deleteAllEntities(BatchDownload::class, $output);
        $this->deleteAllEntities(AbstractDossier::class, $output);
        $this->deleteAllEntities(Document::class, $output);
        $this->deleteAllEntities(Inquiry::class, $output);
        $this->deleteAllEntities(History::class, $output);
        $this->deleteAllEntities(Subject::class, $output);

        if ($input->getOption('users')) {
            $this->deleteAllEntities(User::class, $output);
        }

        if ($input->getOption('prefixes')) {
            $this->deleteAllEntities(DocumentPrefix::class, $output);
        }

        $this->clearContentExtractCache($output);

        return 0;
    }

    private function deleteAllEntities(string $entityClassName, OutputInterface $output): void
    {
        try {
            /** @var literal-string $entityClassName */
            $this->entityManager->createQueryBuilder()->delete($entityClassName)->getQuery()->execute();
        } catch (\Exception $exception) {
            $output->writeln("<error>Error while deleting $entityClassName entities:</error>");
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return;
        }

        $output->writeln("👍 All $entityClassName entities have been deleted");
    }

    public function removeElasticSearchIndex(string $indexName, OutputInterface $output): void
    {
        try {
            $this->indexService->delete($indexName);
        } catch (\Exception $exception) {
            $output->writeln('<error>Error while removing the ES index</error>');
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return;
        }

        $output->writeln('👍 ElasticSearch index removed');
    }

    private function createElasticSearchIndex(string $indexName, OutputInterface $output): void
    {
        try {
            $this->indexService->createLatestWithAliases($indexName);
        } catch (\Exception $exception) {
            $output->writeln('<error>Error while recreating the ES index</error>');
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return;
        }

        $output->writeln('👍 ElasticSearch index created');
    }

    private function clearQueues(OutputInterface $output): void
    {
        try {
            foreach ($this->queueDsns as $queueDsn) {
                $url = str_replace(['amqp', ':5672'], ['http', ':15672/api/queues'], $queueDsn) . '/contents';
                $response = $this->httpClient->request('DELETE', $url);
                if ($response->getStatusCode() !== 204) {
                    throw new \RuntimeException("Purging of queue $queueDsn failed");
                }
            }
        } catch (\Exception $exception) {
            $output->writeln('<error>Error while purging the RabbitMQ queues</error>');
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return;
        }

        $output->writeln('👍 RabbitMQ queues purged');
    }

    private function clearContentExtractCache(OutputInterface $output): void
    {
        $greetInput = new ArrayInput([
            'command' => 'cache:pool:clear',
            'pools' => ['content_extract_cache'],
        ]);

        $this->getApplication()?->doRun($greetInput, $output);
    }
}
