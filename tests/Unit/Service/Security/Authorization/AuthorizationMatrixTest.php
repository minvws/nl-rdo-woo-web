<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Security\Authorization;

use App\Entity\Organisation;
use App\Entity\User;
use App\Service\Security\Authorization\AuthorizationEntryRequestStore;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\Authorization\Entry;
use App\Service\Security\OrganisationSwitcher;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;

class AuthorizationMatrixTest extends MockeryTestCase
{
    private Security&MockInterface $mockSecurity;
    private RequestStack&MockInterface $mockRequestStack;
    private AuthorizationCheckerInterface $authorizationChecker;
    private OrganisationSwitcher&MockInterface $organisationSwitcher;
    private User&MockInterface $user;
    private TokenStorage $tokenStorage;
    private AuthorizationMatrix $authorizationMatrix;
    private AuthorizationEntryRequestStore $entryStore;

    public function setUp(): void
    {
        $this->user = \Mockery::mock(User::class);

        $this->mockSecurity = \Mockery::mock(Security::class);
        $this->mockSecurity->shouldReceive('getUser')->andReturn($this->user);

        $this->mockRequestStack = \Mockery::mock(RequestStack::class);
        $this->entryStore = new AuthorizationEntryRequestStore($this->mockRequestStack);

        $this->tokenStorage = new TokenStorage();
        $voter = new RoleVoter();
        $decisionManager = new AccessDecisionManager([$voter]);
        $this->authorizationChecker = new AuthorizationChecker($this->tokenStorage, $decisionManager);

        $this->organisationSwitcher = \Mockery::mock(OrganisationSwitcher::class);

        $this->authorizationMatrix = new AuthorizationMatrix(
            $this->mockSecurity,
            $this->authorizationChecker,
            $this->organisationSwitcher,
            $this->entryStore,
            $this->getEntries(),
        );
    }

    public function testAdminUser(): void
    {
        $this->setupRoles(['ROLE_ADMIN']);

        $this->assertTrue($this->authorizationMatrix->isAuthorized('user', 'create'));
        $this->assertTrue($this->authorizationMatrix->isAuthorized('user', 'read'));
        $this->assertTrue($this->authorizationMatrix->isAuthorized('user', 'update'));
        $this->assertTrue($this->authorizationMatrix->isAuthorized('user', 'delete'));

        $this->assertFalse($this->authorizationMatrix->isAuthorized('user', 'something_else'));
        $this->assertFalse($this->authorizationMatrix->isAuthorized('document', 'read'));

        $this->assertCount(1, $this->authorizationMatrix->getAuthorizedMatches('user', 'read'));
        $this->assertCount(0, $this->authorizationMatrix->getAuthorizedMatches('user', 'not_existing'));
        $this->assertCount(0, $this->authorizationMatrix->getAuthorizedMatches('document', 'read'));
    }

    public function testRegularUser(): void
    {
        $this->setupRoles(['ROLE_BALIE']);

        $this->assertFalse($this->authorizationMatrix->isAuthorized('user', 'create'));
        $this->assertTrue($this->authorizationMatrix->isAuthorized('user', 'read'));
        $this->assertFalse($this->authorizationMatrix->isAuthorized('user', 'update'));
        $this->assertFalse($this->authorizationMatrix->isAuthorized('user', 'delete'));

        $this->assertFalse($this->authorizationMatrix->isAuthorized('user', 'something_else'));
        $this->assertTrue($this->authorizationMatrix->isAuthorized('document', 'read'));

        $this->assertCount(1, $this->authorizationMatrix->getAuthorizedMatches('user', 'read'));
        $this->assertCount(0, $this->authorizationMatrix->getAuthorizedMatches('user', 'not_existing'));
        $this->assertCount(1, $this->authorizationMatrix->getAuthorizedMatches('document', 'read'));
    }

    public function testFilters(): void
    {
        $this->setupRoles(['ROLE_ADMIN']);

        $entries = $this->getEntries();
        $request = new Request();
        $request->attributes->set(AuthorizationMatrix::AUTH_MATRIX_ATTRIB, [$entries[0]]);
        $this->mockRequestStack->shouldReceive('getCurrentRequest')->andReturn($request);

        // Get the filter for the current entry that matched and is stored in the route
        $this->assertTrue($this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_ORGANISATION_ONLY));
        $this->assertFalse($this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_PUBLISHED_DOSSIERS));
        $this->assertFalse($this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_UNPUBLISHED_DOSSIERS));

        $this->expectException(\RuntimeException::class);
        $this->assertFalse($this->authorizationMatrix->getFilter('unknown_filter'));
    }

    public function testGetActiveOrganisationReturnsTheOrganisationUsingTheOrganisationSwitcher(): void
    {
        $this->setupRoles(['ROLE_BALIE']);

        $organisation = \Mockery::mock(Organisation::class);

        $this->organisationSwitcher->expects('getActiveOrganisation')->with($this->user)->andReturn($organisation);

        $this->assertEquals(
            $organisation,
            $this->authorizationMatrix->getActiveOrganisation(),
        );
    }

    /**
     * @return Entry[]
     */
    protected function getEntries(): array
    {
        return [
            Entry::createFrom([
                'prefix' => 'user',
                'roles' => ['ROLE_ADMIN'],
                'permissions' => [
                    'create' => true,
                    'read' => true,
                    'update' => true,
                    'delete' => true,
                ],
                'filters' => [
                    'organisation_only' => true,
                    'published_dossiers' => false,
                ],
            ]),
            Entry::createFrom([
                'prefix' => 'user',
                'roles' => ['ROLE_BALIE'],
                'permissions' => [
                    'read' => true,
                ],
                'filters' => [
                    'organisation_only' => false,
                    'published_dossiers' => true,
                ],
            ]),
            Entry::createFrom([
                'prefix' => 'document',
                'roles' => ['ROLE_BALIE'],
                'permissions' => [
                    'read' => true,
                ],
                'filters' => [
                    'organisation_only' => false,
                    'unpublished_dossiers' => true,
                ],
            ]),
        ];
    }

    /**
     * @param string[] $roles
     */
    private function setupRoles(array $roles): void
    {
        $this->tokenStorage->setToken(new UsernamePasswordToken($this->user, 'main', $roles));
    }
}
