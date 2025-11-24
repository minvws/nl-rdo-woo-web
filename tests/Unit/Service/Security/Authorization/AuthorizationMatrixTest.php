<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security\Authorization;

use Mockery\MockInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Service\Security\Authorization\AuthorizationEntryRequestStore;
use Shared\Service\Security\Authorization\AuthorizationMatrix;
use Shared\Service\Security\Authorization\AuthorizationMatrixException;
use Shared\Service\Security\Authorization\AuthorizationMatrixFilter;
use Shared\Service\Security\Authorization\Entry;
use Shared\Service\Security\OrganisationSwitcher;
use Shared\Service\Security\User;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;

class AuthorizationMatrixTest extends UnitTestCase
{
    private Security&MockInterface $mockSecurity;
    private RequestStack&MockInterface $mockRequestStack;
    private AuthorizationCheckerInterface $authorizationChecker;
    private OrganisationSwitcher&MockInterface $organisationSwitcher;
    private User&MockInterface $user;
    private TokenStorage $tokenStorage;
    private AuthorizationMatrix $authorizationMatrix;
    private AuthorizationEntryRequestStore $entryStore;

    protected function setUp(): void
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

        self::assertTrue($this->authorizationMatrix->isAuthorized('user', 'create'));
        self::assertTrue($this->authorizationMatrix->isAuthorized('user', 'read'));
        self::assertTrue($this->authorizationMatrix->isAuthorized('user', 'update'));
        self::assertTrue($this->authorizationMatrix->isAuthorized('user', 'delete'));

        self::assertFalse($this->authorizationMatrix->isAuthorized('user', 'something_else'));
        self::assertFalse($this->authorizationMatrix->isAuthorized('document', 'read'));

        self::assertCount(1, $this->authorizationMatrix->getAuthorizedMatches('user', 'read'));
        self::assertCount(0, $this->authorizationMatrix->getAuthorizedMatches('user', 'not_existing'));
        self::assertCount(0, $this->authorizationMatrix->getAuthorizedMatches('document', 'read'));
    }

    public function testRegularUser(): void
    {
        $this->setupRoles(['ROLE_BALIE']);

        self::assertFalse($this->authorizationMatrix->isAuthorized('user', 'create'));
        self::assertTrue($this->authorizationMatrix->isAuthorized('user', 'read'));
        self::assertFalse($this->authorizationMatrix->isAuthorized('user', 'update'));
        self::assertFalse($this->authorizationMatrix->isAuthorized('user', 'delete'));

        self::assertFalse($this->authorizationMatrix->isAuthorized('user', 'something_else'));
        self::assertTrue($this->authorizationMatrix->isAuthorized('document', 'read'));

        self::assertCount(1, $this->authorizationMatrix->getAuthorizedMatches('user', 'read'));
        self::assertCount(0, $this->authorizationMatrix->getAuthorizedMatches('user', 'not_existing'));
        self::assertCount(1, $this->authorizationMatrix->getAuthorizedMatches('document', 'read'));
    }

    public function testHasFilterForAdmin(): void
    {
        $this->setupRoles(['ROLE_ADMIN']);

        $entries = $this->getEntries();
        $request = new Request();
        $request->attributes->set(AuthorizationMatrix::AUTH_MATRIX_ATTRIB, [$entries[0]]);
        $this->mockRequestStack->shouldReceive('getCurrentRequest')->andReturn($request);

        self::assertTrue($this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::ORGANISATION_ONLY));
        self::assertFalse($this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::PUBLISHED_DOSSIERS));
        self::assertTrue($this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS));
    }

    public function testHasFilterForRegularUser(): void
    {
        $this->setupRoles(['ROLE_USER']);

        $entries = $this->getEntries();
        $request = new Request();
        $request->attributes->set(AuthorizationMatrix::AUTH_MATRIX_ATTRIB, [$entries[1]]);
        $this->mockRequestStack->shouldReceive('getCurrentRequest')->andReturn($request);

        self::assertFalse($this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::ORGANISATION_ONLY));
        self::assertTrue($this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::PUBLISHED_DOSSIERS));
        self::assertFalse($this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS));
    }

    public function testGetActiveOrganisationReturnsTheOrganisationUsingTheOrganisationSwitcher(): void
    {
        $this->setupRoles(['ROLE_BALIE']);

        $organisation = \Mockery::mock(Organisation::class);

        $this->organisationSwitcher->expects('getActiveOrganisation')->with($this->user)->andReturn($organisation);

        self::assertEquals(
            $organisation,
            $this->authorizationMatrix->getActiveOrganisation(),
        );
    }

    public function testGetActiveOrganisationThrowsExceptionWhenThereIsNoUser(): void
    {
        $mockSecurity = \Mockery::mock(Security::class);
        $mockSecurity->shouldReceive('getUser')->andReturnNull();

        $authorizationMatrix = new AuthorizationMatrix(
            $mockSecurity,
            $this->authorizationChecker,
            $this->organisationSwitcher,
            $this->entryStore,
            $this->getEntries(),
        );

        $this->expectExceptionObject(AuthorizationMatrixException::forNoActiveUser());

        $authorizationMatrix->getActiveOrganisation();
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
                    'unpublished_dossiers' => true,
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
                    'unpublished_dossiers' => false,
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
