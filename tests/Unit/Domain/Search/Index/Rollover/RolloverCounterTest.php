<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Rollover;

use App\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementConfig;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantConfig;
use App\Domain\Publication\Dossier\Type\DossierTypeManager;
use App\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionConfig;
use App\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;
use App\Domain\Search\Index\Rollover\RolloverCounter;
use App\Service\Elastic\ElasticClientInterface;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Mockery\MockInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class RolloverCounterTest extends UnitTestCase
{
    private RolloverCounter $rolloverCounter;
    private EntityManagerInterface&MockInterface $entityManager;
    private DossierTypeManager&MockInterface $dossierTypeManager;
    private ElasticClientInterface&MockInterface $elasticClient;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->elasticClient = \Mockery::mock(ElasticClientInterface::class);
        $this->dossierTypeManager = \Mockery::mock(DossierTypeManager::class);

        $this->rolloverCounter = new RolloverCounter(
            $this->entityManager,
            $this->elasticClient,
            $this->dossierTypeManager,
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

        $this->elasticClient->expects('search')->andReturn($this->getElasticResponse());

        $this->mockMainTypeRepository(WooDecision::class, 1);
        $this->mockMainTypeRepository(ComplaintJudgement::class, 1);
        $this->mockMainTypeRepository(Covenant::class, 0); // Because of zero count subtypes should not be counted!

        $this->mockSubTypeRepository(Document::class, 8, 16);
        $this->mockSubTypeRepository(WooDecisionMainDocument::class, 1, 1);
        $this->mockSubTypeRepository(WooDecisionAttachment::class, 1, 1, true);
        $this->mockSubTypeRepository(ComplaintJudgementMainDocument::class, 1, 1);

        $this->dossierTypeManager->shouldReceive('getAllConfigs')->andReturn([
            new WooDecisionConfig(\Mockery::mock(WorkflowInterface::class)),
            new ComplaintJudgementConfig(\Mockery::mock(WorkflowInterface::class)),
            new CovenantConfig(\Mockery::mock(WorkflowInterface::class)),
        ]);

        $this->assertMatchesJsonSnapshot(
            $this->rolloverCounter->getEntityCounts($indexDetails)
        );
    }

    private function getElasticResponse(): MockInterface&Elasticsearch
    {
        $json = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'es-count-response.json');
        $esData = json_decode($json ?: '', true);

        $response = \Mockery::mock(Elasticsearch::class);
        $response->shouldReceive('asArray')->andReturn($esData);

        return $response;
    }

    /**
     * @param class-string $entityClass
     */
    private function mockMainTypeRepository(string $entityClass, int $count): void
    {
        $repository = \Mockery::mock(AbstractDossierRepository::class);
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
        $repository = \Mockery::mock(ServiceEntityRepository::class);
        $queryBuilder = \Mockery::mock(QueryBuilder::class);

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
