<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Rollover;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementConfig;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantConfig;
use Shared\Domain\Publication\Dossier\Type\DossierTypeManager;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionConfig;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;
use Shared\Domain\Search\Index\Rollover\RolloverCounter;
use Shared\Service\Elastic\ElasticClientInterface;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Workflow\WorkflowInterface;
use Webmozart\Assert\Assert;

use function count;
use function file_get_contents;
use function json_decode;

use const DIRECTORY_SEPARATOR;

class RolloverCounterTest extends UnitTestCase
{
    private RolloverCounter $rolloverCounter;
    private EntityManagerInterface&MockInterface $entityManager;
    private DossierTypeManager&MockInterface $dossierTypeManager;
    private ElasticClientInterface&MockInterface $elasticClient;
    private SubjectRepository&MockInterface $subjectRepository;

    protected function setUp(): void
    {
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->elasticClient = Mockery::mock(ElasticClientInterface::class);
        $this->dossierTypeManager = Mockery::mock(DossierTypeManager::class);
        $this->subjectRepository = Mockery::mock(SubjectRepository::class);

        $this->rolloverCounter = new RolloverCounter(
            $this->entityManager,
            $this->elasticClient,
            $this->dossierTypeManager,
            $this->subjectRepository,
        );

        parent::setUp();
    }

    public function test(): void
    {
        $indexDetails = new ElasticIndexDetails(
            'index-123',
            'yellow',
            'open',
            '65',
            '69MB',
            '3',
            ['woopie-read'],
        );

        $searchParameters = [];
        $this->elasticClient->expects('search')
            ->with(Mockery::capture($searchParameters))
            ->andReturn($this->getElasticResponse());
        $this->subjectRepository->expects('countWithLandingPage')->andReturn(1);

        $this->mockMainTypeRepository(WooDecision::class, 1);
        $this->mockMainTypeRepository(ComplaintJudgement::class, 1);
        $this->mockMainTypeRepository(Covenant::class, 0); // Because of zero count subtypes should not be counted!

        $this->mockSubTypeRepository(Document::class, 8, 16);
        $this->mockSubTypeRepository(WooDecisionMainDocument::class, 1, 1);
        $this->mockSubTypeRepository(WooDecisionAttachment::class, 1, 1, true);
        $this->mockSubTypeRepository(ComplaintJudgementMainDocument::class, 1, 1);

        $this->dossierTypeManager->expects('getAllConfigs')->times(2)->andReturn([
            new WooDecisionConfig(Mockery::mock(WorkflowInterface::class)),
            new ComplaintJudgementConfig(Mockery::mock(WorkflowInterface::class)),
            new CovenantConfig(Mockery::mock(WorkflowInterface::class)),
        ]);

        $counts = $this->rolloverCounter->getEntityCounts($indexDetails);
        Assert::keyExists($searchParameters, 'body');
        Assert::isArray($searchParameters['body']);
        $body = $searchParameters['body'];
        Assert::keyExists($body, 'aggs');
        Assert::isArray($body['aggs']);
        $aggregations = $body['aggs'];
        Assert::keyExists($aggregations, 'toplevel_type');
        Assert::isArray($aggregations['toplevel_type']);
        $topLevelTypeAggregation = $aggregations['toplevel_type'];
        Assert::keyExists($topLevelTypeAggregation, 'terms');
        Assert::isArray($topLevelTypeAggregation['terms']);
        $terms = $topLevelTypeAggregation['terms'];
        Assert::keyExists($terms, 'size');

        self::assertSame(
            count(ElasticDocumentType::getMainTypes()) + count(ElasticDocumentType::getStandaloneTypes()),
            $terms['size'],
        );
        $this->assertMatchesJsonSnapshot($counts);
    }

    private function getElasticResponse(): MockInterface&Elasticsearch
    {
        $json = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'es-count-response.json');
        $esData = json_decode($json ?: '', true);

        $response = Mockery::mock(Elasticsearch::class);
        $response->expects('asArray')->andReturn($esData);

        return $response;
    }

    public function testSubjectCountReportsMissingDocumentsAndEmptyIndex(): void
    {
        $index = new ElasticIndexDetails('subject-test', 'yellow', 'open', '0', '0', '32', []);
        foreach ([2, 0] as $expectedCount) {
            $response = Mockery::mock(Elasticsearch::class);
            $response->expects('asArray')->andReturn([
                'aggregations' => ['toplevel_type' => ['buckets' => []]],
            ]);
            $this->elasticClient->expects('search')->andReturn($response);
            $this->dossierTypeManager->expects('getAllConfigs')->times(2)->andReturn([]);
            $this->subjectRepository->expects('countWithLandingPage')->andReturn($expectedCount);

            $counts = $this->rolloverCounter->getEntityCounts($index);

            self::assertCount(1, $counts);
            self::assertSame(ElasticDocumentType::SUBJECT, $counts[0]->type);
            self::assertSame($expectedCount, $counts[0]->expected);
            self::assertSame(0, $counts[0]->actual);
            self::assertSame($expectedCount === 0 ? 100.0 : 0.0, $counts[0]->getPercentage());
        }
    }

    /**
     * @param class-string $entityClass
     */
    private function mockMainTypeRepository(string $entityClass, int $count): void
    {
        $repository = Mockery::mock(AbstractDossierRepository::class);
        $repository->expects('count')->with([])->andReturn($count);

        $this->entityManager->expects('getRepository')->with($entityClass)->andReturn($repository);
    }

    /**
     * @param class-string $entityClass
     */
    private function mockSubTypeRepository(
        string $entityClass,
        int $count,
        int $pageCount,
        bool $expectWhere = false,
    ): void {
        $repository = Mockery::mock(ServiceEntityRepository::class);
        $queryBuilder = Mockery::mock(QueryBuilder::class);

        $repository->expects('createQueryBuilder')->andReturn($queryBuilder);

        // Chaining does not work when method return type of method call is static:
        $queryBuilder->expects('select')->andReturnSelf();
        $queryBuilder->expects('addSelect')->andReturnSelf();
        $queryBuilder->expects('getQuery->getSingleResult')->andReturn([
            'count' => $count,
            'pageCount' => $pageCount,
        ]);

        if ($expectWhere) {
            $queryBuilder->expects('where')->andReturnSelf();
        }

        $this->entityManager->expects('getRepository')->with($entityClass)->andReturn($repository);
    }
}
