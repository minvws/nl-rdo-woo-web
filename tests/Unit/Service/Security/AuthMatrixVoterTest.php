<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Service\Security\AuthMatrixVoter;
use Shared\Service\Security\Authorization\AuthorizationEntryRequestStore;
use Shared\Service\Security\Authorization\AuthorizationMatrix;
use Shared\Service\Security\Authorization\AuthorizationMatrixFilter;
use Shared\Service\Security\Authorization\Entry;
use Shared\Service\Security\Roles;
use Shared\Service\Security\User;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AuthMatrixVoterTest extends UnitTestCase
{
    /**
     * @param array<array-key, Entry&MockInterface>|null $entries
     */
    #[DataProvider('voteProvider')]
    public function testVoter(
        string $input,
        int $expectedResult,
        ?string $prefix = null,
        ?string $permission = null,
        ?array $entries = null,
    ): void {
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);
        if ($entries) {
            $entryStore->expects('storeEntries')->with(...$entries);
        }

        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        if ($entries) {
            $authorizationMatrix->expects('isAuthorized')->with($prefix, $permission)->andReturnTrue();
            $authorizationMatrix->expects('getAuthorizedMatches')->with($prefix, $permission)->andReturn($entries);
        } elseif ($prefix !== null && $permission !== null) {
            $authorizationMatrix->expects('isAuthorized')->with($prefix, $permission)->andReturnFalse();
        }

        $user = Mockery::mock(User::class);
        $user->shouldReceive('isEnabled')->andReturnTrue();

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = Mockery::mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn($user);

        self::assertEquals($expectedResult, $voter->vote($token, null, [$input]));
    }

    /**
     * @return array<array-key, array<array-key, mixed>>
     */
    public static function voteProvider(): array
    {
        return [
            'abstain-for-regular-role' => [
                'input' => Roles::ROLE_SUPER_ADMIN,
                'expectedResult' => VoterInterface::ACCESS_ABSTAIN,
            ],
            'grant-for-authmatrix-match-without-subject' => [
                'input' => 'AuthMatrix.dossier.read',
                'expectedResult' => VoterInterface::ACCESS_GRANTED,
                'prefix' => 'dossier',
                'permission' => 'read',
                'entries' => [
                    Mockery::mock(Entry::class),
                ],
            ],
            'denied-for-authmatrix-mismatch-without-subject' => [
                'input' => 'AuthMatrix.dossier.delete',
                'expectedResult' => VoterInterface::ACCESS_DENIED,
                'prefix' => 'dossier',
                'permission' => 'delete',
            ],
            'denied-for-invalid-format' => [
                'input' => 'AuthMatrix.dossier',
                'expectedResult' => VoterInterface::ACCESS_DENIED,
            ],
        ];
    }

    public function testAbstainForUnknownSubject(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('isEnabled')->andReturnTrue();

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = Mockery::mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn($user);

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, new stdClass(), ['AuthMatrix.dossier.read']));
    }

    public function testAccessGrantedForValidSubject(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('isEnabled')->andReturnTrue();

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = Mockery::mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn($user);

        $entry = Mockery::mock(Entry::class);
        $authorizationMatrix->shouldReceive('isAuthorized')->with('dossier', 'read')->andReturnTrue();
        $authorizationMatrix->shouldReceive('getAuthorizedMatches')->with('dossier', 'read')->andReturn([$entry]);
        $authorizationMatrix->shouldReceive('hasFilter')->with(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS)->andReturnTrue();
        $entryStore->expects('storeEntries')->with($entry);

        $subject = Mockery::mock(AbstractDossier::class);
        $subject->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        $organisation = Mockery::mock(Organisation::class);
        $subject->shouldReceive('getOrganisation')->andReturn($organisation);
        $authorizationMatrix->shouldReceive('getActiveOrganisation')->andReturn($organisation);

        self::assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, ['AuthMatrix.dossier.read']));
    }

    public function testAccessDeniedOnSubjectFromDifferentOrganisation(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('isEnabled')->andReturnTrue();

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = Mockery::mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn($user);

        $entry = Mockery::mock(Entry::class);
        $authorizationMatrix->shouldReceive('isAuthorized')->with('dossier', 'read')->andReturnTrue();
        $authorizationMatrix->shouldReceive('getAuthorizedMatches')->with('dossier', 'read')->andReturn([$entry]);
        $entryStore->expects('storeEntries')->with($entry);

        $subject = Mockery::mock(AbstractDossier::class);
        $subject->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        $organisationA = Mockery::mock(Organisation::class);
        $organisationB = Mockery::mock(Organisation::class);
        $subject->shouldReceive('getOrganisation')->andReturn($organisationA);
        $authorizationMatrix->shouldReceive('getActiveOrganisation')->andReturn($organisationB);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, ['AuthMatrix.dossier.read']));
    }

    public function testAccessDeniedOnSubjectPublishedFilterMismatch(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('isEnabled')->andReturnTrue();

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = Mockery::mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn($user);

        $entry = Mockery::mock(Entry::class);
        $authorizationMatrix->shouldReceive('isAuthorized')->with('dossier', 'read')->andReturnTrue();
        $authorizationMatrix->shouldReceive('getAuthorizedMatches')->with('dossier', 'read')->andReturn([$entry]);
        $authorizationMatrix->shouldReceive('hasFilter')->with(AuthorizationMatrixFilter::PUBLISHED_DOSSIERS)->andReturnFalse();
        $entryStore->expects('storeEntries')->with($entry);

        $subject = Mockery::mock(AbstractDossier::class);
        $subject->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $organisation = Mockery::mock(Organisation::class);
        $subject->shouldReceive('getOrganisation')->andReturn($organisation);
        $authorizationMatrix->shouldReceive('getActiveOrganisation')->andReturn($organisation);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, ['AuthMatrix.dossier.read']));
    }

    public function testAccessDeniedOnSubjectUnpublishedFilterMismatch(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('isEnabled')->andReturnTrue();

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = Mockery::mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn($user);

        $entry = Mockery::mock(Entry::class);
        $authorizationMatrix->shouldReceive('isAuthorized')->with('dossier', 'read')->andReturnTrue();
        $authorizationMatrix->shouldReceive('getAuthorizedMatches')->with('dossier', 'read')->andReturn([$entry]);
        $authorizationMatrix->shouldReceive('hasFilter')->with(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS)->andReturnFalse();
        $entryStore->expects('storeEntries')->with($entry);

        $subject = Mockery::mock(AbstractDossier::class);
        $subject->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        $organisation = Mockery::mock(Organisation::class);
        $subject->shouldReceive('getOrganisation')->andReturn($organisation);
        $authorizationMatrix->shouldReceive('getActiveOrganisation')->andReturn($organisation);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, ['AuthMatrix.dossier.read']));
    }

    public function testAccessDeniedOnSubjectIfUserIsDisabled(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('isEnabled')->andReturnFalse();

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = Mockery::mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn($user);

        $subject = Mockery::mock(AbstractDossier::class);
        $subject->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        $organisation = Mockery::mock(Organisation::class);
        $subject->shouldReceive('getOrganisation')->andReturn($organisation);
        $authorizationMatrix->shouldReceive('getActiveOrganisation')->andReturn($organisation);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, ['AuthMatrix.dossier.read']));
    }
}
