<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Service\Search\Query\Definition;

use Admin\Service\Search\Query\Definition\AdminDossiersAndDocumentsQueryDefinition;
use Admin\Tests\Integration\AdminWebTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Search\Query\SearchParametersFactory;
use Shared\Domain\Search\Query\SearchResultType;
use Shared\Service\Security\Authorization\AuthorizationMatrix;
use Shared\Tests\Integration\Service\Search\Query\Definition\QueryDefinitionTestTrait;
use Symfony\Component\Uid\Uuid;

#[Group('search')]
final class AdminDossiersAndDocumentsQueryDefinitionTest extends AdminWebTestCase
{
    use QueryDefinitionTestTrait;

    public function testElasticQueryBuiltFromDefinition(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getId')->andReturn(Uuid::fromRfc4122('55ae5de9-55f4-3420-b50b-5cde6e07fc5a'));

        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $authorizationMatrix->expects('getActiveOrganisation')->andReturn($organisation);
        self::getContainer()->set(AuthorizationMatrix::class, $authorizationMatrix);

        /** @var SearchParametersFactory $searchParametersFactory */
        $searchParametersFactory = self::getContainer()->get(SearchParametersFactory::class);
        $searchParameters = $searchParametersFactory->forAdminSearch(
            'foo',
            DossierType::WOO_DECISION,
            'foo-123',
            SearchResultType::DOSSIER,
        );

        $this->matchDefinitionToSnapshot(AdminDossiersAndDocumentsQueryDefinition::class, $searchParameters);
    }
}
