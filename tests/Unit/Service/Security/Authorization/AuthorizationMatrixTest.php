<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Security\Authorization;

use App\Entity\User;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\Authorization\Entry;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
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
    protected Security|Mockery\MockInterface|Mockery\LegacyMockInterface $mockSecurity;
    protected RequestStack|Mockery\MockInterface|Mockery\LegacyMockInterface $mockRequestStack;
    protected AuthorizationCheckerInterface|Mockery\MockInterface|Mockery\LegacyMockInterface $mockAuthorizationChecker;

    public function testAdminUser()
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $matrix = $this->generateMatrix($user);

        $this->assertTrue($matrix->isAuthorized('user', 'create'));
        $this->assertTrue($matrix->isAuthorized('user', 'read'));
        $this->assertTrue($matrix->isAuthorized('user', 'update'));
        $this->assertTrue($matrix->isAuthorized('user', 'delete'));

        $this->assertFalse($matrix->isAuthorized('user', 'something_else'));
        $this->assertFalse($matrix->isAuthorized('document', 'read'));

        $this->assertCount(1, $matrix->getAuthorizedMatches('user', 'read'));
        $this->assertCount(0, $matrix->getAuthorizedMatches('user', 'not_existing'));
        $this->assertCount(0, $matrix->getAuthorizedMatches('document', 'read'));
    }

    public function testRegularUser()
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $matrix = $this->generateMatrix($user);

        $this->assertFalse($matrix->isAuthorized('user', 'create'));
        $this->assertTrue($matrix->isAuthorized('user', 'read'));
        $this->assertFalse($matrix->isAuthorized('user', 'update'));
        $this->assertFalse($matrix->isAuthorized('user', 'delete'));

        $this->assertFalse($matrix->isAuthorized('user', 'something_else'));
        $this->assertTrue($matrix->isAuthorized('document', 'read'));

        $this->assertCount(1, $matrix->getAuthorizedMatches('user', 'read'));
        $this->assertCount(0, $matrix->getAuthorizedMatches('user', 'not_existing'));
        $this->assertCount(1, $matrix->getAuthorizedMatches('document', 'read'));
    }

    public function testFilters()
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $matrix = $this->generateMatrix($user);

        $entries = $this->getEntries();
        $request = new Request();
        $request->attributes->set(AuthorizationMatrix::AUTH_MATRIX_ATTRIB, [$entries[0]]);
        $this->mockRequestStack->shouldReceive('getCurrentRequest')->andReturn($request)->zeroOrMoreTimes();

        // Get the filter for the current entry that matched and is stored in the route
        $this->assertTrue($matrix->getFilter(AuthorizationMatrix::FILTER_ORGANISATION_ONLY));
        $this->assertFalse($matrix->getFilter(AuthorizationMatrix::FILTER_PUBLISHED_DOSSIERS));
        $this->assertFalse($matrix->getFilter(AuthorizationMatrix::FILTER_UNPUBLISHED_DOSSIERS));

        $this->expectException(\RuntimeException::class);
        $this->assertFalse($matrix->getFilter('unknown_filter'));
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
                'roles' => ['ROLE_USER'],
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
                'roles' => ['ROLE_USER'],
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

    protected function generateMatrix(User $user): AuthorizationMatrix
    {
        $this->mockSecurity = \Mockery::mock(Security::class);
        $this->mockSecurity->shouldReceive('getUser')->andReturn($user)->zeroOrMoreTimes();

        $this->mockRequestStack = \Mockery::mock(RequestStack::class);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        $voter = new RoleVoter();
        $decisionManager = new AccessDecisionManager([$voter]);
        $this->mockAuthorizationChecker = new AuthorizationChecker($tokenStorage, $decisionManager);

        return new AuthorizationMatrix(
            $this->mockSecurity,
            $this->mockRequestStack,
            $this->mockAuthorizationChecker,
            $this->getEntries(),
        );
    }
}
