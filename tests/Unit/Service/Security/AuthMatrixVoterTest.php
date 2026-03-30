<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security;

use Mockery;
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
    public function testVoterAbstainForRegularRole(): void
    {
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);

        $token = Mockery::mock(TokenInterface::class);

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, [Roles::ROLE_SUPER_ADMIN]));
    }

    public function testVoterGrantForAuthMatrixMatchWithoutSubject(): void
    {
        $entries = [Mockery::mock(Entry::class)];

        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);
        $entryStore->expects('storeEntries')->with(...$entries);

        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $authorizationMatrix->expects('isAuthorized')->with('dossier', 'read')->andReturnTrue();
        $authorizationMatrix->expects('getAuthorizedMatches')->with('dossier', 'read')->andReturn($entries);

        $user = Mockery::mock(User::class);
        $user->expects('isEnabled')->andReturnTrue();

        $token = Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);

        self::assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, ['AuthMatrix.dossier.read']));
    }

    public function testVoterDeniedForAuthMatrixMismatchWithoutSubject(): void
    {
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $authorizationMatrix->expects('isAuthorized')->with('dossier', 'delete')->andReturnFalse();

        $user = Mockery::mock(User::class);
        $user->expects('isEnabled')->andReturnTrue();

        $token = Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, ['AuthMatrix.dossier.delete']));
    }

    public function testVoterDeniedForInvalidFormat(): void
    {
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);

        $user = Mockery::mock(User::class);
        $user->expects('isEnabled')->andReturnTrue();

        $token = Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, ['AuthMatrix.dossier']));
    }

    public function testAbstainForUnknownSubject(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = Mockery::mock(TokenInterface::class);

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, new stdClass(), ['AuthMatrix.dossier.read']));
    }

    public function testAccessGrantedForValidSubject(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $user = Mockery::mock(User::class);
        $user->expects('isEnabled')->andReturnTrue();

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $entry = Mockery::mock(Entry::class);
        $authorizationMatrix->expects('isAuthorized')->with('dossier', 'read')->andReturnTrue();
        $authorizationMatrix->expects('getAuthorizedMatches')->with('dossier', 'read')->andReturn([$entry]);
        $authorizationMatrix->expects('hasFilter')->with(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS)->andReturnTrue();
        $entryStore->expects('storeEntries')->with($entry);

        $organisation = Mockery::mock(Organisation::class);
        $authorizationMatrix->expects('getActiveOrganisation')->andReturn($organisation);

        $subject = Mockery::mock(AbstractDossier::class);
        $subject->expects('getStatus')->times(2)->andReturn(DossierStatus::CONCEPT);
        $subject->expects('getOrganisation')->andReturn($organisation);

        self::assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, ['AuthMatrix.dossier.read']));
    }

    public function testAccessDeniedOnSubjectFromDifferentOrganisation(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $user = Mockery::mock(User::class);
        $user->expects('isEnabled')->andReturnTrue();

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $entry = Mockery::mock(Entry::class);
        $authorizationMatrix->expects('isAuthorized')->with('dossier', 'read')->andReturnTrue();
        $authorizationMatrix->expects('getAuthorizedMatches')->with('dossier', 'read')->andReturn([$entry]);
        $entryStore->expects('storeEntries')->with($entry);

        $organisationA = Mockery::mock(Organisation::class);
        $organisationB = Mockery::mock(Organisation::class);
        $authorizationMatrix->expects('getActiveOrganisation')->andReturn($organisationB);

        $subject = Mockery::mock(AbstractDossier::class);
        $subject->expects('getOrganisation')->andReturn($organisationA);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, ['AuthMatrix.dossier.read']));
    }

    public function testAccessDeniedOnSubjectPublishedFilterMismatch(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $user = Mockery::mock(User::class);
        $user->expects('isEnabled')->andReturnTrue();

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $entry = Mockery::mock(Entry::class);
        $authorizationMatrix->expects('isAuthorized')->with('dossier', 'read')->andReturnTrue();
        $authorizationMatrix->expects('getAuthorizedMatches')->with('dossier', 'read')->andReturn([$entry]);
        $authorizationMatrix->expects('hasFilter')->with(AuthorizationMatrixFilter::PUBLISHED_DOSSIERS)->andReturnFalse();
        $entryStore->expects('storeEntries')->with($entry);

        $subject = Mockery::mock(AbstractDossier::class);
        $subject->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $organisation = Mockery::mock(Organisation::class);
        $subject->expects('getOrganisation')->andReturn($organisation);
        $authorizationMatrix->expects('getActiveOrganisation')->andReturn($organisation);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, ['AuthMatrix.dossier.read']));
    }

    public function testAccessDeniedOnSubjectUnpublishedFilterMismatch(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $user = Mockery::mock(User::class);
        $user->expects('isEnabled')->andReturnTrue();

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $entry = Mockery::mock(Entry::class);
        $authorizationMatrix->expects('isAuthorized')->with('dossier', 'read')->andReturnTrue();
        $authorizationMatrix->expects('getAuthorizedMatches')->with('dossier', 'read')->andReturn([$entry]);
        $authorizationMatrix->expects('hasFilter')->with(AuthorizationMatrixFilter::UNPUBLISHED_DOSSIERS)->andReturnFalse();
        $entryStore->expects('storeEntries')->with($entry);

        $organisation = Mockery::mock(Organisation::class);
        $authorizationMatrix->expects('getActiveOrganisation')->andReturn($organisation);

        $subject = Mockery::mock(AbstractDossier::class);
        $subject->expects('getStatus')->times(2)->andReturn(DossierStatus::CONCEPT);
        $subject->expects('getOrganisation')->andReturn($organisation);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, ['AuthMatrix.dossier.read']));
    }

    public function testAccessDeniedOnSubjectIfUserIsDisabled(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $entryStore = Mockery::mock(AuthorizationEntryRequestStore::class);

        $user = Mockery::mock(User::class);
        $user->expects('isEnabled')->andReturnFalse();

        $voter = new AuthMatrixVoter($authorizationMatrix, $entryStore);
        $token = Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $subject = Mockery::mock(AbstractDossier::class);

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, ['AuthMatrix.dossier.read']));
    }
}
